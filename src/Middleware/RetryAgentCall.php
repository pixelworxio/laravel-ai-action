<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Middleware;

use Closure;
use Illuminate\Support\Sleep;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Contracts\AgentActionMiddleware;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Exceptions\AgentException;

/**
 * Retries a failed agent call a fixed number of times with configurable backoff.
 *
 * Only AgentException is caught — the exception RunAgentAction wraps every
 * provider-level failure in — so unrelated exceptions still propagate
 * immediately. Uses Illuminate\Support\Sleep so retries are instant and
 * assertable under Sleep::fake() in tests.
 */
final readonly class RetryAgentCall implements AgentActionMiddleware
{
    /**
     * @param  int  $times  Total number of attempts, including the first one.
     * @param  array<int, int>  $backoffSeconds  Seconds to wait after each failed attempt, indexed
     *                                           from the first attempt. The last value repeats for any attempt beyond the array length.
     */
    public function __construct(
        private int $times = 3,
        private array $backoffSeconds = [1, 5, 10],
    ) {}

    public function handle(AgentAction $agent, AgentContext $context, Closure $next): AgentResult
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return $next($agent, $context);
            } catch (AgentException $e) {
                if ($attempt >= $this->times) {
                    throw $e;
                }

                Sleep::for($this->backoffFor($attempt))->seconds();
            }
        }
    }

    private function backoffFor(int $attempt): int
    {
        if ($this->backoffSeconds === []) {
            return 0;
        }

        return $this->backoffSeconds[$attempt - 1] ?? $this->backoffSeconds[array_key_last($this->backoffSeconds)];
    }
}
