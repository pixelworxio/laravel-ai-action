# Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=ai-action-config
```

## Keys

| Key | Environment Variable | Default | Description |
|---|---|---|---|
| `provider` | `AI_ACTION_PROVIDER` | `"anthropic"` | Default provider key passed to `laravel/ai`. |
| `model` | `AI_ACTION_MODEL` | `"claude-sonnet-4-20250514"` | Default model identifier. |
| `queue` | `AI_ACTION_QUEUE` | `"default"` | Queue name for `RunAgentActionJob`. |
| `max_tokens` | `AI_ACTION_MAX_TOKENS` | `2048` | Maximum output tokens per request. |
| `logging` | `AI_ACTION_LOGGING` | `false` | Log invocations via `Log::info('ai-action.executed', …)`. |

## Per-Action Override

Override `provider()` or `model()` directly on any action class:

```php
final class GptClassify implements AgentAction
{
    use InteractsWithAgent;

    public function provider(): string { return 'openai'; }
    public function model(): string { return 'gpt-4o'; }

    // ...
}
```
