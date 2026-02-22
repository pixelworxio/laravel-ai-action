![laravel-ai-action](https://socialify.git.ci/pixelworxio/laravel-ai-action/image?custom_description=Structured%2C+testable+AI+actions+for+Laravel+%E2%80%94+built+on+laravel%2Fai.&custom_language=Laravel&description=1&language=1&name=1&owner=1&pattern=Solid&theme=Auto)

<p align="center">
  <a href="https://packagist.org/packages/pixelworxio/laravel-ai-action"><img src="https://img.shields.io/packagist/v/pixelworxio/laravel-ai-action" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/pixelworxio/laravel-ai-action"><img src="https://img.shields.io/packagist/dt/pixelworxio/laravel-ai-action" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/pixelworxio/laravel-ai-action"><img src="https://img.shields.io/packagist/l/pixelworxio/laravel-ai-action" alt="License"></a>
  <a href="https://github.com/pixelworxio/laravel-ai-action/actions/workflows/run-tests.yml"><img src="https://img.shields.io/github/actions/workflow/status/pixelworxio/laravel-ai-action/run-tests.yml?branch=main&label=tests&style=flat-square" alt="GitHub Tests Action Status"></a>
  <a href="https://github.com/pixelworxio/laravel-ai-action"><img src="https://img.shields.io/github/stars/pixelworxio/laravel-ai-action?style=flat-square" alt="GitHub Stars"></a>
</p>

---

## What does this package do?
This package offers an architectural pattern that sits on top of `laravel/ai` to provide a consistent, structured, and testable way to execute AI actions in your Laravel app.

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
