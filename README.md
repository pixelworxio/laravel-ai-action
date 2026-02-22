# laravel-ai-action

<p align="center">
  <img src=".github/art/banner.png" alt="laravel-ai-action" width="100%">
</p>

<p align="center">
  <a href="https://packagist.org/packages/pixelworxio/laravel-ai-action"><img src="https://img.shields.io/packagist/v/pixelworxio/laravel-ai-action" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/pixelworxio/laravel-ai-action"><img src="https://img.shields.io/packagist/dt/pixelworxio/laravel-ai-action" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/pixelworxio/laravel-ai-action"><img src="https://img.shields.io/packagist/l/pixelworxio/laravel-ai-action" alt="License"></a>
</p>

**Structured, testable AI actions for Laravel** — built on [`laravel/ai`](https://github.com/laravel/ai).

---

## Why not just use `laravel/ai` directly?

`laravel/ai` is an excellent SDK. This package is not a replacement — it's an **architectural pattern** on top of it.

| | `laravel/ai` | `laravel-ai-action` |
|---|---|---|
| **Abstraction level** | Agents, tools, streaming primitives | Single-responsibility action classes |
| **Context passing** | Manual | `AgentContext` DTO (record, meta, user instruction) |
| **Output handling** | Raw response objects | Typed `AgentResult` with token tracking |
| **Structured output** | `StructuredAnonymousAgent` | `HasStructuredOutput` + `mapOutput()` |
| **Streaming** | Iterator + event handling | `HasStreamingResponse` callbacks |
| **Queue support** | None built-in | `RunAgentActionJob` (unique, queueable) |
| **Testing** | Mock the SDK | `FakeAgentAction` + fluent assertions |
| **Artisan scaffolding** | None | `php artisan make:ai-action` |

If you're wiring AI calls directly into controllers or service classes, you're reinventing this. `laravel-ai-action` gives every AI capability in your app a **consistent, discoverable home** — the same way `laravel/actions` does for business logic.

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

Publish the config to customise defaults:

```bash
php artisan vendor:publish --tag=ai-action-config
```

---

## Quick Start

```bash
php artisan make:ai-action SummarisePost
```

```php
// app/Ai/Actions/SummarisePost.php
final class SummarisePost implements AgentAction
{
    use InteractsWithAgent;

    public function instructions(AgentContext $context): string
    {
        return 'You are a concise technical writer. Summarise in three sentences.';
    }

    public function prompt(AgentContext $context): string
    {
        return sprintf("Summarise:\n\n%s", $context->record->body);
    }

    public function handle(AgentContext $context): AgentResult
    {
        return app(RunAgentAction::class)->execute($this, $context);
    }
}
```

```php
// In a controller or job
$context = AgentContext::fromRecord($post);
$result  = $this->runner->execute(new SummarisePost(), $context);

echo $result->text;         // "This post covers..."
echo $result->inputTokens;  // 320
```

---

## Documentation

- [**Actions**](docs/actions.md) — creating actions, contracts, and execution modes
- [**Context**](docs/context.md) — `AgentContext` reference and usage
- [**Results**](docs/results.md) — `AgentResult` reference and usage
- [**Testing**](docs/testing.md) — `FakeAgentAction` and fluent assertions
- [**Configuration**](docs/configuration.md) — all config keys and environment variables
- [**Queue**](docs/queue.md) — background execution with `RunAgentActionJob`

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT — see [LICENSE](LICENSE).
