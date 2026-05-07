<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Mcp;

use Laravel\Mcp\Server;

/**
 * Base MCP server that wires AI Action tools from the Bridge.
 *
 * Extend this class in your application to expose registered AI actions as MCP
 * tools. The boot() hook pulls all tools built by Bridge::flush() into the
 * server's $tools array at start time. You still need to register the server
 * as a route via the Mcp facade:
 *
 * @example
 * ```php
 * // app/Mcp/AppServer.php
 * use Pixelworxio\LaravelAiAction\Mcp\AgentActionServer;
 *
 * class AppServer extends AgentActionServer {}
 *
 * // routes/mcp.php  (or AppServiceProvider::boot)
 * use Laravel\Mcp\Facades\Mcp;
 *
 * Mcp::web(AppServer::class);
 * ```
 *
 * This class is not required for testing — tests access built tools directly
 * via app(Bridge::class)->tools() without needing a Server instance.
 */
abstract class AgentActionServer extends Server
{
    /**
     * Push Bridge-built tools into the server's $tools array.
     */
    protected function boot(): void
    {
        parent::boot();

        /** @var Bridge $bridge */
        $bridge = app(Bridge::class);

        foreach ($bridge->tools() as $tool) {
            $this->tools[] = $tool;
        }
    }
}
