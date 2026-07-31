# Middleware

`AgentAction` classes can wrap their execution in a middleware pipeline, the same way queued jobs do in Laravel. Implement `HasMiddleware` and return the stages you want, outermost first:

```php
use Pixelworxio\LaravelAiAction\Contracts\HasMiddleware;
use Pixelworxio\LaravelAiAction\Middleware\FallbackProvider;
use Pixelworxio\LaravelAiAction\Middleware\Idempotent;
use Pixelworxio\LaravelAiAction\Middleware\RetryAgentCall;

final class SummarizeInvoice implements AgentAction, HasMiddleware
{
    use InteractsWithAgent;

    public function middleware(): array
    {
        return [
            new Idempotent(ttl: now()->addHour()),
            new FallbackProvider(['openai']),
            new RetryAgentCall(times: 3, backoffSeconds: [1, 5, 10]),
        ];
    }

    // instructions(), prompt(), handle() ...
}
```

`RunAgentAction` composes these around the real provider call exactly like `Illuminate\Pipeline`: the first entry is outermost. The recommended order above matters — a cached hit from `Idempotent` should short-circuit everything below it, and `FallbackProvider` should wrap `RetryAgentCall` so each provider gets its own retry budget rather than retries being spent on a provider that's actually down.

## Idempotent

Short-circuits a repeat call for the same agent + context, returning the cached `AgentResult` instead of calling the provider again.

```php
new Idempotent(ttl: 3600, store: null, key: null);
```

- `ttl` — how long a result is reused for (seconds, `DateInterval`, or `DateTimeInterface`).
- `store` — cache store name; `null` uses the default store.
- `key` — an explicit cache key. When omitted, one is derived from the agent class plus the context's record key(s), meta, and user instruction — not the whole model, so it stays small and stable.

This serves two purposes at once: it prevents duplicate side-effecting calls (a queued job retried by the worker) and it avoids paying for a repeat prompt/context combination.

## RetryAgentCall

Retries on `AgentException` — the exception every provider-level failure gets wrapped in — with configurable backoff.

```php
new RetryAgentCall(times: 3, backoffSeconds: [1, 5, 10]);
```

`times` is the total number of attempts, including the first. `backoffSeconds` is indexed from the first failed attempt; the last value repeats for any attempt beyond the array length. Backoff uses `Illuminate\Support\Sleep`, so it's instant and assertable under `Sleep::fake()` in tests.

## FallbackProvider

Retries the same call against alternate providers, in order, after the agent's own `provider()` fails.

```php
new FallbackProvider([
    'openai',
    ['provider' => 'openai', 'model' => 'gpt-4o-mini'],
]);
```

Each entry is either a bare provider key (keeping the agent's own `model()`) or `['provider' => ..., 'model' => ...]` when the fallback needs a different model. The agent's own provider is always tried first.

## Writing your own middleware

Implement `AgentActionMiddleware`:

```php
use Pixelworxio\LaravelAiAction\Contracts\AgentActionMiddleware;

final class LogSlowCalls implements AgentActionMiddleware
{
    public function handle(AgentAction $agent, AgentContext $context, Closure $next): AgentResult
    {
        $start = hrtime(true);

        $result = $next($agent, $context);

        if ((hrtime(true) - $start) / 1_000_000 > 5000) {
            Log::warning('Slow AI action', ['agent' => $agent::class]);
        }

        return $result;
    }
}
```

`$next` invokes the next stage of the pipeline — either the next middleware or the real provider call. You may inspect, replace, or short-circuit around it, and you may pass a modified `AgentContext` onward (e.g. `$context->withProvider(...)`).
