<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Mcp\Facades;

use Illuminate\Support\Facades\Facade;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Mcp\Bridge;
use Pixelworxio\LaravelAiAction\Mcp\Contracts\ExposedAsMcpTool;
use Pixelworxio\LaravelAiAction\Mcp\Registration;

/**
 * Facade for registering AI actions as MCP tools.
 *
 * Use this in your service provider's boot() method or in a dedicated
 * routes/ai.php file to expose agent actions to the Laravel MCP server.
 *
 * @see Bridge
 *
 * @method static Registration tool(class-string<AgentAction&ExposedAsMcpTool> $actionClass) Register an action as an MCP tool.
 */
final class AiActionMcp extends Facade
{
    /**
     * Return the facade accessor.
     */
    protected static function getFacadeAccessor(): string
    {
        return Bridge::class;
    }
}
