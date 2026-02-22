# laravel-ai-action

AI-powered actions for Laravel — a clean integration layer built on `laravel/ai`.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.4` |
| Laravel | `^12.0` |
| `laravel/ai` | `^0.1` |

---

## Installation

```bash
composer require pixelworxio/laravel-ai-action
```

The service provider is auto-discovered. Publish the config if you want to customise defaults:

```bash
php artisan vendor:publish --tag=ai-action-config
```

---

## Quick Start

### 1. Generate an agent

```bash
php artisan make:agent SummarisePost
```

This creates `app/Ai/Agents/SummarisePost.php` pre-wired with the `AgentAction` contract and `InteractsWithAgent` trait.

### 2. Inject and call `RunAgentAction`

```php
<?php

declare(strict_types=1);

use App\Ai\Agents\SummarisePost;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;

class PostController
{
    public function __construct(private readonly RunAgentAction $runner) {}

    public function summarise(Post $post): string
    {
        $context = AgentContext::fromRecord($post);
        $result  = $this->runner->execute(new SummarisePost(), $context);

        return $result->text;
    }
}
```

---

## Creating an Agent

Every agent is a plain PHP class implementing `AgentAction`. Use the `InteractsWithAgent` trait to satisfy `provider()` and `model()` from config, leaving you only three methods to define.

```php
<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Concerns\InteractsWithAgent;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;

final class SummarisePost implements AgentAction
{
    use InteractsWithAgent;

    /**
     * System-level instructions sent to the model on every request.
     * Keep these stable — they shape the model's behaviour and tone.
     */
    public function instructions(AgentContext $context): string
    {
        return 'You are a concise technical writer. Summarise blog posts in three sentences.';
    }

    /**
     * The user-facing prompt built from the runtime context.
     * Pull content from $context->record, $context->meta, etc.
     */
    public function prompt(AgentContext $context): string
    {
        /** @var \App\Models\Post $post */
        $post = $context->record;

        return sprintf("Summarise the following post:\n\n%s", $post->body);
    }

    /**
     * Execute the action. Delegate to RunAgentAction — do not put business
     * logic here. Pre/post-processing belongs in instructions() and prompt().
     */
    public function handle(AgentContext $context): AgentResult
    {
        return app(RunAgentAction::class)->execute($this, $context);
    }
}
```

---

## Contracts

### `AgentAction`

The core contract every agent must implement.

```php
<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;

final class HelloAgent implements AgentAction
{
    public function instructions(AgentContext $context): string
    {
        return 'You are a friendly greeter.';
    }

    public function prompt(AgentContext $context): string
    {
        return 'Say hello to the user.';
    }

    public function provider(): string
    {
        return 'anthropic'; // override per-agent when needed
    }

    public function model(): string
    {
        return 'claude-haiku-4-5-20251001'; // cheap model for simple tasks
    }

    public function handle(AgentContext $context): AgentResult
    {
        return app(\Pixelworxio\LaravelAiAction\Actions\RunAgentAction::class)->execute($this, $context);
    }
}
```

**When to use:** Always — every agent implements this. Use `InteractsWithAgent` to inherit `provider()` / `model()` from config, then only define `instructions()`, `prompt()`, and `handle()`.

---

### `HasStructuredOutput`

Tells `RunAgentAction` to activate structured JSON schema mode. The model is constrained to return data matching your schema; `mapOutput()` converts the raw array to whatever your application expects.

```php
<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Concerns\InteractsWithAgent;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Contracts\HasStructuredOutput;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;

final class ClassifyPost implements AgentAction, HasStructuredOutput
{
    use InteractsWithAgent;

    public function instructions(AgentContext $context): string
    {
        return 'Classify blog posts by topic and sentiment.';
    }

    public function prompt(AgentContext $context): string
    {
        return 'Classify: ' . $context->record->body;
    }

    public function handle(AgentContext $context): AgentResult
    {
        return app(RunAgentAction::class)->execute($this, $context);
    }

    /** JSON schema the model must conform to. */
    public function outputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'topic'     => ['type' => 'string'],
                'sentiment' => ['type' => 'string', 'enum' => ['positive', 'neutral', 'negative']],
                'confidence' => ['type' => 'number'],
            ],
            'required' => ['topic', 'sentiment', 'confidence'],
        ];
    }

    /** Map the raw array to a typed domain value. */
    public function mapOutput(array $raw): mixed
    {
        return new PostClassification(
            topic: $raw['topic'],
            sentiment: $raw['sentiment'],
            confidence: $raw['confidence'],
        );
    }
}
```

**When to use:** When you need deterministic, machine-readable output — classifications, extractions, entity recognition, or any data you'll pass to another system.

---

### `HasTools`

Exposes Laravel AI SDK Tool instances to the model, allowing it to call functions during generation (RAG, HTTP requests, database lookups, etc.).

```php
<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Concerns\InteractsWithAgent;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Contracts\HasTools;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use App\Ai\Tools\SearchDocsTool;
use App\Ai\Tools\FetchUrlTool;

final class ResearchAgent implements AgentAction, HasTools
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

    /** Return Tool instances — registered automatically by RunAgentAction. */
    public function tools(): array
    {
        return [
            new SearchDocsTool(),
            new FetchUrlTool(),
        ];
    }
}
```

**When to use:** When your agent needs to call application code or external services during inference — document retrieval, API lookups, calculations, or any agentic loop.

---

### `HasStreamingResponse`

Switches `RunAgentAction` into streaming mode. Each text delta fires `onChunk()`; returning `false` halts the stream. `onComplete()` fires once with the final `AgentResult`.

```php
<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Support\Facades\Cache;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Concerns\InteractsWithAgent;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Contracts\HasStreamingResponse;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;

final class StreamingWriterAgent implements AgentAction, HasStreamingResponse
{
    use InteractsWithAgent;

    private string $buffer = '';

    public function instructions(AgentContext $context): string
    {
        return 'You are a long-form content writer.';
    }

    public function prompt(AgentContext $context): string
    {
        return 'Write an article about: ' . $context->meta['topic'];
    }

    public function handle(AgentContext $context): AgentResult
    {
        return app(RunAgentAction::class)->execute($this, $context);
    }

    /** Receive each text delta. Return false to stop the stream early. */
    public function onChunk(string $chunk): bool
    {
        $this->buffer .= $chunk;
        // broadcast $chunk to a Livewire component, WebSocket, etc.
        return true; // continue streaming
    }

    /** Called once the stream is complete. Persist, broadcast, or log here. */
    public function onComplete(AgentResult $result): void
    {
        Cache::put('last_article', $result->text, now()->addHour());
    }
}
```

**When to use:** Long-form generation where you want to display output progressively (Livewire, SSE, WebSockets) or halt early based on a token budget.

---

## `AgentContext` Reference

`AgentContext` is an immutable `final readonly` DTO that carries all runtime data into an agent invocation.

### Properties

| Property | Type | Description |
|---|---|---|
| `$record` | `?Model` | The primary Eloquent record (single-record actions). |
| `$records` | `array<int, Model>` | Batch of Eloquent records (bulk actions). |
| `$meta` | `array<string, mixed>` | Arbitrary key/value data for prompt building. |
| `$userInstruction` | `?string` | Free-text instruction from the end user. |
| `$panelId` | `?string` | Filament panel identifier (set by filament-ai-action). |
| `$resourceClass` | `?string` | Filament resource class (set by filament-ai-action). |

### Static Constructors

```php
// Single Eloquent record
$context = AgentContext::fromRecord($post);
$context = AgentContext::fromRecord($post, ['tone' => 'formal']);

// Batch of records
$context = AgentContext::fromRecords($posts);
$context = AgentContext::fromRecords($posts, ['language' => 'en']);
```

### `withMeta()` — immutable mutation

`withMeta()` returns a **new** `AgentContext` instance; the original is never modified.

```php
$base = AgentContext::fromRecord($order);

// Each call produces a new instance — $base is unchanged
$withTone    = $base->withMeta('tone', 'formal');
$withToneAndLang = $withTone->withMeta('language', 'en');

// $base->meta === []
// $withTone->meta === ['tone' => 'formal']
// $withToneAndLang->meta === ['tone' => 'formal', 'language' => 'en']
```

This makes it safe to share a base context across multiple agents without mutation side-effects.

---

## `AgentResult` Reference

`AgentResult` is an immutable `final readonly` DTO wrapping the AI provider response.

### Properties

| Property | Type | Description |
|---|---|---|
| `$text` | `string` | Raw text returned by the model. |
| `$format` | `OutputFormat` | `Text`, `Structured`, or `Markdown`. |
| `$structured` | `mixed` | Mapped structured value (`null` for non-structured output). |
| `$inputTokens` | `int` | Number of prompt tokens consumed. |
| `$outputTokens` | `int` | Number of completion tokens generated. |
| `$provider` | `string` | Provider key used (e.g. `"anthropic"`). |
| `$model` | `string` | Model identifier used (e.g. `"claude-sonnet-4-20250514"`). |
| `$metadata` | `array<string, mixed>` | Additional provider-specific metadata. |

### Methods

```php
$result->isStructured(); // true when $format === OutputFormat::Structured
$result->toArray();      // serialize all properties to an associative array
```

### Example

```php
$result = $runner->execute(new SummarisePost(), $context);

echo $result->text;          // "This post covers..."
echo $result->inputTokens;   // 320
echo $result->outputTokens;  // 48
echo $result->provider;      // "anthropic"
echo $result->model;         // "claude-sonnet-4-20250514"

if ($result->isStructured()) {
    $dto = $result->structured; // your mapOutput() return value
}
```

---

## Testing

### Faking responses with `FakeAgentAction`

`FakeAgentAction` replaces `RunAgentAction` in the container. No real API calls are ever made.

```php
<?php

declare(strict_types=1);

use App\Ai\Agents\SummarisePost;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\Testing\AgentActionAssertions;
use Pixelworxio\LaravelAiAction\Testing\FakeAgentAction;

beforeEach(fn () => FakeAgentAction::reset());

it('summarises a post', function (): void {
    // 1. Register a fake response before the system-under-test runs
    FakeAgentAction::fakeResponse(
        SummarisePost::class,
        'This post covers unit testing in PHP.',
    );

    // 2. Run the code that triggers the agent
    $post    = Post::factory()->create();
    $context = AgentContext::fromRecord($post);
    $result  = app(\Pixelworxio\LaravelAiAction\Actions\RunAgentAction::class)
        ->execute(new SummarisePost(), $context);

    // 3. Assert the result using fluent AgentActionAssertions
    AgentActionAssertions::for($result)
        ->assertText('This post covers unit testing in PHP.')
        ->assertIsText()
        ->assertProvider('fake')
        ->assertModel('fake')
        ->assertInputTokens(0)
        ->assertOutputTokens(0);

    // 4. Assert invocation counts
    FakeAgentAction::assertAgentCalled(SummarisePost::class, 1);
});

it('does not call the summarise agent when post is a draft', function (): void {
    $post = Post::factory()->draft()->create();

    // run code path that should skip the agent for drafts…

    FakeAgentAction::assertAgentNotCalled(SummarisePost::class);
});
```

### Faking structured output

```php
it('classifies a post', function (): void {
    $structured = ['topic' => 'testing', 'sentiment' => 'positive', 'confidence' => 0.92];

    FakeAgentAction::fakeResponse(
        ClassifyPost::class,
        '{"topic":"testing","sentiment":"positive","confidence":0.92}',
        $structured,
    );

    $context = AgentContext::fromRecord(Post::factory()->create());
    $result  = app(\Pixelworxio\LaravelAiAction\Actions\RunAgentAction::class)
        ->execute(new ClassifyPost(), $context);

    AgentActionAssertions::for($result)
        ->assertIsStructured()
        ->assertStructured($structured);
});
```

### `AgentActionAssertions` reference

| Method | Description |
|---|---|
| `AgentActionAssertions::for($result)` | Create a fluent assertions instance. |
| `->assertText(string $expected)` | Assert exact text match. |
| `->assertTextContains(string $needle)` | Assert text contains substring. |
| `->assertIsStructured()` | Assert format is `Structured`. |
| `->assertIsText()` | Assert format is not `Structured`. |
| `->assertStructured(mixed $expected)` | Assert structured value equals expected. |
| `->assertProvider(string $expected)` | Assert provider key. |
| `->assertModel(string $expected)` | Assert model identifier. |
| `->assertInputTokens(int $expected)` | Assert input token count. |
| `->assertOutputTokens(int $expected)` | Assert output token count. |

---

## Config Reference

Publish with `php artisan vendor:publish --tag=ai-action-config`.

| Key | Env var | Default | Controls |
|---|---|---|---|
| `provider` | `AI_ACTION_PROVIDER` | `anthropic` | The `laravel/ai` provider key used by `InteractsWithAgent`. Override per-agent via `provider()`. |
| `model` | `AI_ACTION_MODEL` | `claude-sonnet-4-20250514` | The model identifier used by `InteractsWithAgent`. Override per-agent via `model()`. |
| `queue` | `AI_ACTION_QUEUE` | `default` | The queue name used by `RunAgentActionJob` when dispatching background jobs. |
| `max_tokens` | `AI_ACTION_MAX_TOKENS` | `2048` | Maximum tokens to generate per invocation. |
| `logging` | `AI_ACTION_LOGGING` | `false` | When `true`, logs each invocation (provider, model, token counts) via Laravel's `Log::info`. |

```php
// config/ai-action.php
return [
    'provider'   => env('AI_ACTION_PROVIDER', 'anthropic'),
    'model'      => env('AI_ACTION_MODEL', 'claude-sonnet-4-20250514'),
    'queue'      => env('AI_ACTION_QUEUE', 'default'),
    'max_tokens' => env('AI_ACTION_MAX_TOKENS', 2048),
    'logging'    => env('AI_ACTION_LOGGING', false),
];
```
