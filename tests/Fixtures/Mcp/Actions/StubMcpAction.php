<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Concerns\InteractsWithAgent;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Mcp\Attributes\ExposesAsMcpTool;
use Pixelworxio\LaravelAiAction\Mcp\Concerns\BridgesAgentContextToMcp;
use Pixelworxio\LaravelAiAction\Mcp\Contracts\ExposedAsMcpTool;

/**
 * Minimal concrete action used in MCP bridge unit and feature tests.
 */
#[ExposesAsMcpTool]
class StubMcpAction implements AgentAction, ExposedAsMcpTool
{
    use BridgesAgentContextToMcp;
    use InteractsWithAgent;

    public function instructions(AgentContext $context): string
    {
        return 'You are a test assistant.';
    }

    public function prompt(AgentContext $context): string
    {
        return (string) ($context->meta['prompt'] ?? 'test prompt');
    }

    public function handle(AgentContext $context): AgentResult
    {
        return app(RunAgentAction::class)->execute($this, $context);
    }

    public function mcpName(): string
    {
        return 'stub_mcp_action';
    }

    public function mcpDescription(): string
    {
        return 'A stub action for testing the MCP bridge.';
    }

    public function mcpInputSchema(JsonSchema $schema): array
    {
        return [
            'prompt' => $schema->string()->required()->description('The test prompt.'),
        ];
    }

    public function resolveContext(array $input, ?Authenticatable $user): AgentContext
    {
        return new AgentContext(
            record: null,
            records: [],
            meta: ['prompt' => $input['prompt'] ?? ''],
            userInstruction: null,
            panelId: null,
            resourceClass: null,
        );
    }
}
