<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Mcp\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\Exceptions\InvalidContextException;

/**
 * Marks an AgentAction as exposable as an MCP tool.
 *
 * Implement this interface alongside AgentAction to enable the action to be
 * registered with the Laravel MCP server. The action explicitly declares its
 * MCP surface: the tool name, description, input schema, and context resolver.
 *
 * Auth scoping, Eloquent resolution, and meta stamping are all the action's
 * responsibility inside resolveContext(). The bridge delivers the authenticated
 * user but enforces nothing — gating belongs where it always has.
 *
 * @example
 * ```php
 * #[ExposesAsMcpTool]
 * #[IsReadOnly]
 * final class SummarizeInvoice implements AgentAction, ExposedAsMcpTool
 * {
 *     use InteractsWithAgent;
 *     use BridgesAgentContextToMcp;
 *
 *     public function mcpName(): string { return 'summarize_invoice'; }
 *     public function mcpDescription(): string { return 'Summarize a single invoice.'; }
 *
 *     public function mcpInputSchema(JsonSchema $schema): array
 *     {
 *         return [
 *             'invoice_id' => $schema->integer()->required()->description('Invoice primary key.'),
 *         ];
 *     }
 *
 *     public function resolveContext(array $input, ?Authenticatable $user): AgentContext
 *     {
 *         return AgentContext::fromRecord($this->resolveRecord(Invoice::class, $input['invoice_id'], $user));
 *     }
 * }
 * ```
 */
interface ExposedAsMcpTool
{
    /**
     * Return the tool name as the MCP client sees it.
     *
     * Use snake_case. This is the identifier LLM clients use to invoke the tool
     * (e.g. "summarize_invoice"). It must be unique across all registered tools.
     *
     * @return string The MCP tool name.
     */
    public function mcpName(): string;

    /**
     * Return a one-line description surfaced to the LLM client.
     *
     * Write this for the model, not the developer: describe what the tool does
     * and when to call it. Keep it under 200 characters.
     *
     * @return string The MCP tool description.
     */
    public function mcpDescription(): string;

    /**
     * Declare the MCP input schema as a map of property name → JsonSchema Type.
     *
     * Uses the same Illuminate JsonSchema factory the package already uses
     * internally for HasStructuredOutput. Every property needed to build the
     * AgentContext must appear here with an accurate type and description so the
     * LLM client can supply correct values.
     *
     * @param  JsonSchema  $schema  The JsonSchema factory.
     * @return array<string, Type> A flat map of property name to Type instance.
     */
    public function mcpInputSchema(JsonSchema $schema): array;

    /**
     * Translate validated MCP input into an AgentContext.
     *
     * This is where auth scoping, Eloquent resolution, and meta stamping occur.
     * The $user is the authenticated caller resolved from the MCP HTTP transport
     * (null for stdio / unauthenticated channels — gate appropriately).
     *
     * @param  array<string, mixed>  $input  The validated input from the MCP request.
     * @param  Authenticatable|null  $user  The authenticated user, or null.
     * @return AgentContext The context ready for the action's handle() method.
     *
     * @throws InvalidContextException
     *                                 When the input cannot be resolved into a valid context.
     */
    public function resolveContext(array $input, ?Authenticatable $user): AgentContext;
}
