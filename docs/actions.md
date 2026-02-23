# Actions

Every AI capability is a plain PHP class implementing `AgentAction`. Use `InteractsWithAgent` to satisfy `provider()` and `model()` from config, leaving only three methods to define.

## Generating an Action

```bash
php artisan make:ai-action SummarizePost
```

Creates `app/Ai/Actions/SummarizePost.php` pre-wired with the `AgentAction` contract and `InteractsWithAgent` trait.

## `AgentAction` Contract

| Method | Returns | Description |
|---|---|---|
| `instructions(AgentContext)` | `string` | System-level prompt shaping model behaviour. |
| `prompt(AgentContext)` | `string` | User-facing prompt built from runtime context. |
| `provider()` | `string` | Provider key (e.g. `"anthropic"`). |
| `model()` | `string` | Model identifier (e.g. `"claude-sonnet-4-20250514"`). |
| `handle(AgentContext)` | `AgentResult` | Entry point — delegate to `RunAgentAction::execute()`. |

## Optional Capability Contracts

Implement one or more alongside `AgentAction` to unlock additional execution modes. `RunAgentAction` inspects for these interfaces at runtime.

### `HasStructuredOutput`

Forces the model to conform to a JSON schema. `mapOutput()` receives the decoded array and returns whatever shape your application expects.

```php
final class ClassifyPost implements AgentAction, HasStructuredOutput
{
    use InteractsWithAgent;

    public function instructions(AgentContext $context): string
    {
        return 'Classify the post and return structured JSON only.';
    }

    public function prompt(AgentContext $context): string
    {
        return 'Classify: ' . $context->record->body;
    }

    public function handle(AgentContext $context): AgentResult
    {
        return app(RunAgentAction::class)->execute($this, $context);
    }

    public function outputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'topic'      => ['type' => 'string'],
                'sentiment'  => ['type' => 'string', 'enum' => ['positive', 'neutral', 'negative']],
                'confidence' => ['type' => 'number'],
            ],
            'required' => ['topic', 'sentiment', 'confidence'],
        ];
    }

    public function mapOutput(array $raw): mixed
    {
        return new PostClassification(
            topic:      $raw['topic'],
            sentiment:  $raw['sentiment'],
            confidence: $raw['confidence'],
        );
    }
}
```

### `HasTools`

Exposes Laravel AI SDK `Tool` instances to the model, enabling agentic loops (RAG, HTTP requests, database lookups).

```php
final class ResearchAction implements AgentAction, HasTools
{
    use InteractsWithAgent;

    public function instructions(AgentContext $context): string
    {
        return 'You are a research assistant with access to documentation and the web.';
    }

    public function prompt(AgentContext $context): string
    {
        return $context->meta['question'];
    }

    public function handle(AgentContext $context): AgentResult
    {
        return app(RunAgentAction::class)->execute($this, $context);
    }

    public function tools(): array
    {
        return [
            new SearchDocsTool(),
            new FetchUrlTool(),
        ];
    }
}
```

`HasTools` composes with all other contracts — tools are registered regardless of execution mode.

### `HasStreamingResponse`

Switches `RunAgentAction` into streaming mode. Each text delta fires `onChunk()`; returning `false` halts the stream. `onComplete()` fires once with the final `AgentResult`.

```php
final class StreamSummary implements AgentAction, HasStreamingResponse
{
    use InteractsWithAgent;

    public function instructions(AgentContext $context): string
    {
        return 'You are a concise technical writer.';
    }

    public function prompt(AgentContext $context): string
    {
        return sprintf("Summarize:\n\n%s", $context->record->body);
    }

    public function handle(AgentContext $context): AgentResult
    {
        return app(RunAgentAction::class)->execute($this, $context);
    }

    public function onChunk(string $delta): bool
    {
        // Broadcast to a Livewire component, SSE channel, etc.
        // Return false to halt the stream early.
        broadcast(new SummaryChunkEvent($delta));

        return true;
    }

    public function onComplete(AgentResult $result): void
    {
        // Persist the final result or dispatch follow-up jobs.
    }
}
```

## Execution Priority

When multiple contracts are implemented, `RunAgentAction` selects the branch in this order:

1. `HasStructuredOutput`
2. `HasStreamingResponse`
3. Default (plain text)

`HasTools` is orthogonal — tools are always registered when the interface is present.
