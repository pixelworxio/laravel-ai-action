<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Facades;

use Illuminate\Support\Facades\Facade;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;

/**
 * Facade providing static access to the RunAgentAction singleton.
 *
 * @method static AgentResult execute(\Pixelworxio\LaravelAiAction\Contracts\AgentAction $agent, AgentContext $context)
 *
 * @see RunAgentAction
 */
final class AgentAction extends Facade
{
    /**
     * Return the service container binding key for this facade.
     *
     * @return string The class name used to resolve the underlying instance.
     */
    protected static function getFacadeAccessor(): string
    {
        return RunAgentAction::class;
    }
}
