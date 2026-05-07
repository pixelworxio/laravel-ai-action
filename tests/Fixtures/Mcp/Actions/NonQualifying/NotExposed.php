<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions\NonQualifying;

use Pixelworxio\LaravelAiAction\Concerns\InteractsWithAgent;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;

/**
 * Implements AgentAction but NOT ExposedAsMcpTool — should be excluded by scanner.
 */
class NotExposed implements AgentAction
{
    use InteractsWithAgent;

    public function instructions(AgentContext $context): string
    {
        return '';
    }

    public function prompt(AgentContext $context): string
    {
        return '';
    }

    public function handle(AgentContext $context): AgentResult
    {
        return new AgentResult('', OutputFormat::Text, null, 0, 0, 'anthropic', 'model', []);
    }
}
