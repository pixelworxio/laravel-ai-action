<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Contracts;

use Closure;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;

/**
 * A single stage in an agent action's execution pipeline.
 *
 * Mirrors Laravel's queued job middleware: implementations wrap the call to
 * $next() with their own behaviour (retrying, short-circuiting via a cache,
 * substituting the context passed onward, etc.) and must return the
 * AgentResult that $next() ultimately produces (or one of their own).
 */
interface AgentActionMiddleware
{
    /**
     * Handle the agent action invocation.
     *
     * @param  AgentAction  $agent  The agent action being executed.
     * @param  AgentContext  $context  The runtime context for this invocation.
     * @param  Closure(AgentAction, AgentContext): AgentResult  $next  Invokes the next
     *                                                                 stage of the pipeline (either the next middleware or the real execution).
     * @return AgentResult The result to propagate back up the pipeline.
     */
    public function handle(AgentAction $agent, AgentContext $context, Closure $next): AgentResult;
}
