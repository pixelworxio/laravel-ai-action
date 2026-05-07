<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Mcp;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Exceptions\InvalidContextException;
use Pixelworxio\LaravelAiAction\Mcp\Contracts\ExposedAsMcpTool;

/**
 * Adapts an AgentAction + ExposedAsMcpTool into a Laravel MCP Tool.
 *
 * This is the bridge adapter. It wraps an action that implements both
 * AgentAction (the execution contract) and ExposedAsMcpTool (the MCP surface
 * declaration), delegating name/description/schema to the action and routing
 * handle() calls through the action's resolveContext() → handle() pipeline.
 *
 * Annotations (#[IsReadOnly], #[IsDestructive], etc.) are declared on the
 * underlying action class. This adapter reads them via reflection and exposes
 * them through annotations() so Laravel MCP can include them in the tool
 * descriptor. PHP attributes placed directly on the action class are forwarded
 * to the MCP protocol layer without any magic on the action author's part.
 *
 * This class must NOT be instantiated when laravel/mcp is absent. The service
 * provider guards all code paths that would load this file behind a
 * class_exists(\Laravel\Mcp\Server\Tool::class) check so PSR-4 lazy autoload
 * keeps this file cold when the optional dependency is not installed.
 */
final class AgentActionMcpTool extends Tool
{
    /**
     * The action instance, typed as the intersection of both required contracts.
     */
    private readonly AgentAction&ExposedAsMcpTool $action;

    /**
     * The responder that maps AgentResult → Laravel\Mcp\Response.
     */
    private readonly AgentResultResponder $responder;

    /**
     * Optional name override from the Registration builder.
     */
    private ?string $nameOverride = null;

    /**
     * @param  AgentAction&ExposedAsMcpTool  $action  The action to wrap.
     * @param  AgentResultResponder|null  $responder  Custom responder; defaults to AgentResultResponder.
     */
    public function __construct(
        AgentAction&ExposedAsMcpTool $action,
        ?AgentResultResponder $responder = null,
    ) {
        $this->action = $action;
        $this->responder = $responder ?? new AgentResultResponder;
    }

    /**
     * Override the tool name (set by the Registration builder).
     *
     * @param  string  $name  The name to advertise to MCP clients.
     * @return $this
     */
    public function withName(string $name): static
    {
        $this->nameOverride = $name;

        return $this;
    }

    /**
     * Return the MCP tool name.
     *
     * Uses the Registration-level override if set; otherwise delegates to the
     * action's mcpName().
     */
    public function name(): string
    {
        return $this->nameOverride ?? $this->action->mcpName();
    }

    /**
     * Return the MCP tool description.
     */
    public function description(): string
    {
        return $this->action->mcpDescription();
    }

    /**
     * Build the MCP input schema from the action's mcpInputSchema() declaration.
     *
     * Delegates directly to the action, which uses the same Illuminate JsonSchema
     * factory the package already uses for HasStructuredOutput — no duplication.
     *
     * @param  JsonSchema  $schema  The JsonSchema factory provided by Laravel MCP.
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->action->mcpInputSchema($schema);
    }

    /**
     * Handle an incoming MCP tool call.
     *
     * Resolves the AgentContext from the validated request input and the
     * authenticated user, executes the action, and maps the AgentResult to
     * an MCP Response via AgentResultResponder.
     *
     * InvalidContextException (bad input / missing record) is caught and mapped
     * to Response::error() so the MCP client receives a structured error rather
     * than an unhandled exception. All other exceptions propagate so the MCP
     * transport's error handler can deal with them.
     *
     * @param  Request  $request  The incoming MCP HTTP request.
     * @return Response|array<int, Response>
     */
    public function handle(Request $request): Response|array
    {
        $input = $request->all();
        $user = $request->user();

        try {
            $context = $this->action->resolveContext($input, $user);
        } catch (InvalidContextException $e) {
            return Response::error($e->getMessage());
        }

        $result = $this->action->handle($context);

        return $this->responder->respond($result, $this->action);
    }

    /**
     * Forward PHP annotation attributes from the underlying action class.
     *
     * Laravel MCP reads annotations to populate the tool descriptor (readonly,
     * destructive, idempotent, open-world hints). Because those attributes are
     * declared on the action class — not on this generic adapter — this method
     * reads them via reflection and returns a key → value map matching the
     * parent's array<string, mixed> contract.
     *
     * Each qualifying attribute must expose a key(): string method and a public
     * $value property (the AnnotationContract duck-type). Attributes that do not
     * satisfy this interface are silently skipped.
     *
     * @return array<string, mixed>
     */
    public function annotations(): array
    {
        $reflection = new \ReflectionClass($this->action);
        $result = [];

        foreach ($reflection->getAttributes() as $attribute) {
            if (! str_starts_with($attribute->getName(), 'Laravel\\Mcp\\')) {
                continue;
            }

            $instance = $attribute->newInstance();

            if (method_exists($instance, 'key') && property_exists($instance, 'value')) {
                $result[$instance->key()] = $instance->value;
            }
        }

        return $result;
    }
}
