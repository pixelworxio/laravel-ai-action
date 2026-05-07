<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Mcp\Attributes;

/**
 * Marks an AgentAction class for automatic MCP tool discovery.
 *
 * Place this attribute on any class that implements both AgentAction and
 * ExposedAsMcpTool to opt into auto-discovery. The AttributeScanner will
 * find it within the paths configured in config('ai-action.mcp.discover_in')
 * and register it with the MCP server automatically.
 *
 * Auto-discovery is the secondary registration path. The primary path is
 * explicit registration via AiActionMcp::tool() in your service provider,
 * which gives you per-action overrides (custom name, custom responder).
 *
 * @example
 * ```php
 * use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
 * use Pixelworxio\LaravelAiAction\Mcp\Attributes\ExposesAsMcpTool;
 * use Pixelworxio\LaravelAiAction\Mcp\Contracts\ExposedAsMcpTool as ExposedAsMcpToolContract;
 *
 * #[ExposesAsMcpTool]
 * #[IsReadOnly]
 * final class SummarizeInvoice implements AgentAction, ExposedAsMcpToolContract { ... }
 * ```
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ExposesAsMcpTool {}
