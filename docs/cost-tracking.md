# Cost Tracking

Every `AgentResult` already carries `inputTokens` and `outputTokens`. `cost()` turns those into a USD figure using the per-million-token rates in `config('ai-action.pricing')`:

```php
$result = $this->runner->execute(new SummarizePost(), $context);

$result->cost(); // 0.0042, or null if unpriced
```

## Configuring rates

```php
// config/ai-action.php
'pricing' => [
    'anthropic' => [
        'claude-sonnet-4-20250514' => ['input' => 3.00, 'output' => 15.00],
    ],
    'openai' => [
        'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
    ],
],
```

Rates are keyed by provider, then the exact model identifier, as USD per million tokens. A missing provider/model pair makes `cost()` return `null` rather than `0.0` — silently reporting "free" would be misleading when it's really "no data."

Provider pricing changes frequently. The shipped defaults are illustrative starting points, not a live price feed — check your provider's current pricing page before relying on these for budgeting, and keep the config updated as models change.

`cost()` is also included in `AgentResult::toArray()`, and feeds the `ai_action` metric on the [Pulse card](pulse.md) when that's enabled.
