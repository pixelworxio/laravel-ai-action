<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Concerns\InteractsWithAgent;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Mcp\Attributes\ExposesAsMcpTool;
use Pixelworxio\LaravelAiAction\Mcp\Concerns\BridgesAgentContextToMcp;
use Pixelworxio\LaravelAiAction\Mcp\Contracts\ExposedAsMcpTool;

/**
 * Action carrying a native MCP annotation to test annotation forwarding.
 */
#[ExposesAsMcpTool]
#[IsReadOnly]
class StubReadOnlyMcpAction implements AgentAction, ExposedAsMcpTool
{
    use BridgesAgentContextToMcp;
    use InteractsWithAgent;

    public function instructions(AgentContext $context): string
    {
        return 'Read-only test assistant.';
    }

    public function prompt(AgentContext $context): string
    {
        return 'readonly test';
    }

    public function handle(AgentContext $context): AgentResult
    {
        return app(RunAgentAction::class)->execute($this, $context);
    }

    public function mcpName(): string
    {
        return 'stub_readonly_action';
    }

    public function mcpDescription(): string
    {
        return 'A read-only stub action.';
    }

    public function mcpInputSchema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->required()->description('Query string.'),
        ];
    }

    public function resolveContext(array $input, ?Authenticatable $user): AgentContext
    {
        return new AgentContext(
            record: null,
            records: [],
            meta: $input,
            userInstruction: null,
            panelId: null,
            resourceClass: null,
        );
    }
}
