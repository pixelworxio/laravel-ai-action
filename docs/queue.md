# Queue

`RunAgentActionJob` wraps any `AgentAction` for background execution. It implements `ShouldQueue` and `ShouldBeUnique`, deriving its uniqueness key from the agent class name and a hash of the serialised context — preventing duplicate jobs for identical inputs.

## Dispatching

```php
use Pixelworxio\LaravelAiAction\Jobs\RunAgentActionJob;

$context = AgentContext::fromRecord($post);

RunAgentActionJob::dispatch(new SummarisePost(), $context);

// On a specific queue
RunAgentActionJob::dispatch(new SummarisePost(), $context)->onQueue('ai');
```

The queue name defaults to `config('ai-action.queue')` (`AI_ACTION_QUEUE` env var).

## Handling the Result

The job calls `handle()` on the action, which delegates to `RunAgentAction::execute()`. Persist the result inside the action's `handle()` method or `onComplete()` callback:

```php
public function handle(AgentContext $context): AgentResult
{
    $result = app(RunAgentAction::class)->execute($this, $context);

    $context->record->update(['summary' => $result->text]);

    return $result;
}
```
