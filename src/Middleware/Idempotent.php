<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Middleware;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Contracts\AgentActionMiddleware;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;

/**
 * Short-circuits an agent call when an identical invocation was already run
 * recently, returning the cached AgentResult instead of calling the provider
 * again.
 *
 * Serves two purposes at once: it prevents duplicate side-effecting calls
 * (the classic idempotency-key use case, e.g. a queued job retried by the
 * worker) and it avoids paying for a repeat prompt/context combination. The
 * cache key is derived from the agent class plus the record key(s), meta,
 * and user instruction on the context — not the whole model — so it stays
 * small and stable across requests.
 */
final readonly class Idempotent implements AgentActionMiddleware
{
    /**
     * @param  DateTimeInterface|DateInterval|int  $ttl  How long a result is reused for. An int is
     *                                                   treated as seconds, matching Laravel's Cache::put() signature.
     * @param  string|null  $store  Optional cache store name; null uses the default store.
     * @param  string|null  $key  Optional explicit cache key. When omitted a key is derived from
     *                            the agent class and context.
     */
    public function __construct(
        private DateTimeInterface|DateInterval|int $ttl = 3600,
        private ?string $store = null,
        private ?string $key = null,
    ) {}

    public function handle(AgentAction $agent, AgentContext $context, Closure $next): AgentResult
    {
        $cache = Cache::store($this->store);
        $key = $this->key ?? $this->keyFor($agent, $context);

        $cached = $cache->get($key);

        if ($cached instanceof AgentResult) {
            return $cached;
        }

        $result = $next($agent, $context);

        $cache->put($key, $result, $this->ttl);

        return $result;
    }

    private function keyFor(AgentAction $agent, AgentContext $context): string
    {
        return 'ai-action:idempotent:'.sha1($agent::class.'|'.$this->hashContext($context));
    }

    private function hashContext(AgentContext $context): string
    {
        return sha1((string) json_encode([
            'record' => $context->record?->getKey(),
            'records' => array_map(static fn ($record) => $record->getKey(), $context->records),
            'meta' => $context->meta,
            'userInstruction' => $context->userInstruction,
            'providerOverride' => $context->providerOverride,
            'modelOverride' => $context->modelOverride,
        ]));
    }
}
