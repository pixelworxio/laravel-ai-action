<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Middleware;

use Closure;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Contracts\AgentActionMiddleware;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Exceptions\AgentException;

/**
 * Falls back to alternate providers, in order, when the agent's own
 * provider() fails.
 *
 * The agent's configured provider/model is always tried first. Each fallback
 * entry may be a bare provider key (keeping the agent's own model()) or an
 * array shape ['provider' => ..., 'model' => ...] when the fallback provider
 * needs a different model identifier.
 */
final readonly class FallbackProvider implements AgentActionMiddleware
{
    /**
     * @param  array<int, string|array{provider: string, model?: string}>  $providers  Fallback
     *                                                                                 providers to try, in order, after the agent's own provider fails.
     */
    public function __construct(private array $providers) {}

    public function handle(AgentAction $agent, AgentContext $context, Closure $next): AgentResult
    {
        $lastException = null;

        foreach ([null, ...$this->providers] as $candidate) {
            try {
                return $next($agent, $this->contextFor($context, $candidate));
            } catch (AgentException $e) {
                $lastException = $e;
            }
        }

        throw $lastException;
    }

    /**
     * @param  string|array{provider: string, model?: string}|null  $candidate
     */
    private function contextFor(AgentContext $context, string|array|null $candidate): AgentContext
    {
        if ($candidate === null) {
            return $context;
        }

        return is_array($candidate)
            ? $context->withProvider($candidate['provider'], $candidate['model'] ?? null)
            : $context->withProvider($candidate);
    }
}
