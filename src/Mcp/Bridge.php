<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Mcp;

use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Mcp\Contracts\ExposedAsMcpTool;
use Pixelworxio\LaravelAiAction\Mcp\Discovery\AttributeScanner;

/**
 * Collects and builds AI action → MCP tool registrations.
 *
 * Bound as a singleton by LaravelAiActionServiceProvider. The AiActionMcp
 * facade delegates to this class. Registrations accumulate via tool() calls
 * (typically from service providers or routes/ai.php) and are built during
 * flush() — called in an app()->booted() callback so that all service providers
 * have a chance to register tools before any are committed.
 *
 * Built tools are stored in $builtTools and exposed via tools(). An
 * AgentActionServer subclass can pull these tools into its $tools array via boot().
 *
 * Auto-discovered actions (via AttributeScanner) are added during flush() so
 * that explicit registrations take precedence over discovered ones.
 */
final class Bridge
{
    /**
     * Accumulated registrations, keyed by action class name to prevent duplicates.
     *
     * @var array<class-string, Registration>
     */
    private array $registrations = [];

    /**
     * Built tool adapters, populated by flush().
     *
     * @var list<AgentActionMcpTool>
     */
    private array $builtTools = [];

    /**
     * Register an action class as an MCP tool.
     *
     * Returns a fluent Registration builder so callers can override the tool
     * name, responder, or other per-tool settings before the flush.
     *
     * @param  class-string<AgentAction&ExposedAsMcpTool>  $actionClass
     */
    public function tool(string $actionClass): Registration
    {
        $registration = new Registration($actionClass);
        $this->registrations[$actionClass] = $registration;

        return $registration;
    }

    /**
     * Build all pending registrations and store the resulting tool adapters.
     *
     * Also processes any auto-discovered action classes from AttributeScanner,
     * skipping classes already registered explicitly. Called once from the
     * service provider's booted() callback.
     */
    public function flush(): void
    {
        $this->addDiscovered();

        foreach ($this->registrations as $registration) {
            $this->builtTools[] = $registration->build();
        }

        $this->registrations = [];
    }

    /**
     * Return all built tool adapters.
     *
     * Available after flush() has been called. Used by AgentActionServer::boot()
     * and by test helpers to inspect registered tools.
     *
     * @return list<AgentActionMcpTool>
     */
    public function tools(): array
    {
        return $this->builtTools;
    }

    /**
     * Reset all registrations and built tools.
     *
     * Used in tests to isolate each test case.
     */
    public function reset(): void
    {
        $this->registrations = [];
        $this->builtTools = [];
    }

    /**
     * Add auto-discovered actions that have not already been explicitly registered.
     */
    private function addDiscovered(): void
    {
        /** @var list<string> $discoverIn */
        $discoverIn = array_values(
            array_filter((array) config('ai-action.mcp.discover_in', []), 'is_string'),
        );

        if ($discoverIn === []) {
            return;
        }

        $scanner = app(AttributeScanner::class);

        foreach ($scanner->scan($discoverIn) as $actionClass) {
            if (! isset($this->registrations[$actionClass])) {
                $this->registrations[$actionClass] = new Registration($actionClass);
            }
        }
    }
}
