# Context

`AgentContext` is an immutable `final readonly` DTO carrying all runtime data into an agent invocation.

## Properties

| Property | Type | Description |
|---|---|---|
| `$record` | `?Model` | Primary Eloquent record (single-record actions). |
| `$records` | `array<int, Model>` | Batch of Eloquent records (bulk actions). |
| `$meta` | `array<string, mixed>` | Arbitrary key/value data for prompt building. |
| `$userInstruction` | `?string` | Free-text instruction from the end user. |

## Static Constructors

```php
$context = AgentContext::fromRecord($post);
$context = AgentContext::fromRecord($post, ['tone' => 'formal']);

$context = AgentContext::fromRecords($posts);
$context = AgentContext::fromRecords($posts, ['language' => 'en']);
```

## `withMeta()` — Immutable Mutation

Returns a **new** `AgentContext` instance; the original is unchanged.

```php
$base = AgentContext::fromRecord($order);

$withTone         = $base->withMeta('tone', 'formal');
$withToneAndLang  = $withTone->withMeta('language', 'en');

// $base->meta          === []
// $withTone->meta      === ['tone' => 'formal']
// $withToneAndLang->meta === ['tone' => 'formal', 'language' => 'en']
```

Safe to share a base context across multiple agents without mutation side-effects.

## Accessing Context in Actions

Use `InteractsWithContext` for helper methods inside an agent:

```php
final class SummarizePost implements AgentAction
{
    use InteractsWithAgent, InteractsWithContext;

    public function prompt(AgentContext $context): string
    {
        $post     = $this->requireRecord($context, Post::class); // throws InvalidContextException if missing
        $language = $this->meta($context, 'language', 'en');     // with default

        return sprintf("Summarize in %s:\n\n%s", $language, $post->body);
    }
}
```

| Method | Description |
|---|---|
| `requireRecord($context, $class)` | Returns `$context->record`, throws `InvalidContextException` if null or wrong type. |
| `requireMeta($context, $key)` | Returns `$context->meta[$key]`, throws `InvalidContextException` if missing. |
| `meta($context, $key, $default)` | Returns `$context->meta[$key]` with a fallback default. |
| `hasRecordOf($context, $class)` | Returns `true` if `$context->record` is an instance of `$class`. |
