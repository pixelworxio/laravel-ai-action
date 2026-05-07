<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Mcp;

use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Mcp\Contracts\ExposedAsMcpTool;

/**
 * Fluent builder that accumulates per-tool overrides before building the adapter.
 *
 * Returned by AiActionMcp::tool(). Callers may chain override methods before
 * the registration is built. Bridge::flush() calls build() on every pending
 * registration and stores the resulting AgentActionMcpTool instances internally.
 * All tool() calls (including those from other service providers' boot() methods)
 * are captured before any are built.
 *
 * @example
 * ```php
 * AiActionMcp::tool(DraftReply::class)->name('draft_reply_v2');
 * AiActionMcp::tool(SummarizeInvoice::class)->responder(new MyCustomResponder());
 * ```
 */
final class Registration
{
    /**
     * Optional name override for this tool.
     */
    private ?string $nameOverride = null;

    /**
     * Optional custom responder for this tool.
     */
    private ?AgentResultResponder $responder = null;

    /**
     * @param  class-string<AgentAction&ExposedAsMcpTool>  $actionClass
     */
    public function __construct(
        private readonly string $actionClass,
    ) {}

    /**
     * Override the tool name advertised to MCP clients.
     *
     * Useful when the action's mcpName() is auto-generated or conflicts with
     * another tool. The override is purely at the registration level — the
     * action class itself is not modified.
     *
     * @param  string  $name  The tool name to use instead.
     * @return $this
     */
    public function name(string $name): static
    {
        $this->nameOverride = $name;

        return $this;
    }

    /**
     * Override the responder that maps AgentResult → MCP Response for this tool.
     *
     * @param  AgentResultResponder  $responder  The custom responder instance.
     * @return $this
     */
    public function responder(AgentResultResponder $responder): static
    {
        $this->responder = $responder;

        return $this;
    }

    /**
     * Build the AgentActionMcpTool adapter for this registration.
     *
     * Instantiates the action and adapter, applies any overrides, and returns
     * the ready tool instance. Called by Bridge::flush() during the application's
     * booted() callback.
     */
    public function build(): AgentActionMcpTool
    {
        /** @var AgentAction&ExposedAsMcpTool $action */
        $action = app($this->actionClass);

        $adapter = new AgentActionMcpTool($action, $this->responder);

        if ($this->nameOverride !== null) {
            $adapter->withName($this->nameOverride);
        }

        return $adapter;
    }
}
