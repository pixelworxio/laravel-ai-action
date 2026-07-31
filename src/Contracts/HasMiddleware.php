<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Contracts;

/**
 * Indicates that an agent action wraps its execution in a middleware pipeline.
 *
 * When RunAgentAction detects this interface, the returned middleware are
 * applied in the order given — the first entry is outermost, the same
 * convention Laravel uses for queued job middleware. A common order is
 * caching/idempotency first (so a hit short-circuits everything below it),
 * then provider fallback, then retry closest to the real call.
 */
interface HasMiddleware
{
    /**
     * Return the middleware pipeline to wrap this action's execution in.
     *
     * @return array<int, AgentActionMiddleware> The middleware, outermost first.
     */
    public function middleware(): array;
}
