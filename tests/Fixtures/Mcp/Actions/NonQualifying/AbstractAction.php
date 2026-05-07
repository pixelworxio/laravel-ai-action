<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions\NonQualifying;

use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Mcp\Attributes\ExposesAsMcpTool;
use Pixelworxio\LaravelAiAction\Mcp\Contracts\ExposedAsMcpTool as ExposedAsMcpToolContract;

/**
 * Abstract class — should be excluded by the scanner even though it carries #[ExposesAsMcpTool].
 */
#[ExposesAsMcpTool]
abstract class AbstractAction implements AgentAction, ExposedAsMcpToolContract
{
    abstract public function instructions(AgentContext $context): string;

    abstract public function prompt(AgentContext $context): string;

    abstract public function handle(AgentContext $context): AgentResult;
}
