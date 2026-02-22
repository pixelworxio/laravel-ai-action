# Results

`AgentResult` is an immutable `final readonly` DTO wrapping the AI provider response.

## Properties

| Property | Type | Description |
|---|---|---|
| `$text` | `string` | Raw text returned by the model. |
| `$format` | `OutputFormat` | `Text`, `Structured`, or `Markdown`. |
| `$structured` | `mixed` | Mapped structured value (`null` for non-structured output). |
| `$inputTokens` | `int` | Prompt tokens consumed. |
| `$outputTokens` | `int` | Completion tokens generated. |
| `$provider` | `string` | Provider key used (e.g. `"anthropic"`). |
| `$model` | `string` | Model identifier used (e.g. `"claude-sonnet-4-20250514"`). |
| `$metadata` | `array<string, mixed>` | Additional provider-specific metadata. |

## Methods

```php
$result->isStructured(); // true when $format === OutputFormat::Structured
$result->toArray();      // serialize all properties to an associative array
```

## Example

```php
$context = AgentContext::fromRecord($post);
$result  = $runner->execute(new SummarisePost(), $context);

echo $result->text;         // "This post covers..."
echo $result->inputTokens;  // 320
echo $result->outputTokens; // 48
echo $result->provider;     // "anthropic"
echo $result->model;        // "claude-sonnet-4-20250514"

if ($result->isStructured()) {
    $dto = $result->structured; // your mapOutput() return value
}
```
