<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions\NonQualifying;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\Mcp\Attributes\ExposesAsMcpTool;
use Pixelworxio\LaravelAiAction\Mcp\Contracts\ExposedAsMcpTool as ExposedAsMcpToolContract;

/**
 * Implements ExposedAsMcpTool but NOT AgentAction — should be excluded by scanner.
 */
#[ExposesAsMcpTool]
class NotAnAction implements ExposedAsMcpToolContract
{
    public function mcpName(): string
    {
        return 'not_an_action';
    }

    public function mcpDescription(): string
    {
        return '';
    }

    public function mcpInputSchema(JsonSchema $schema): array
    {
        return [];
    }

    public function resolveContext(array $input, ?Authenticatable $user): AgentContext
    {
        return AgentContext::fromRecords([]);
    }
}
