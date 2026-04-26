# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/pixelworxio/laravel-ai-action/compare/1.0.3...HEAD)

### Added

- Laravel 13 support (`laravel/framework: ^12.0 || ^13.0`)
- `laravel/ai` v0.3 support (`laravel/ai: ^0.1 || ^0.2 || ^0.3`)

## [1.0.0](https://github.com/pixelworxio/laravel-ai-action/releases/tag/v1.0.0) - unreleased

### Added

- **`AgentAction` contract** — core interface defining `instructions()`, `prompt()`, `provider()`, `model()`, and `handle()` methods that every agent must implement.
- **`HasStructuredOutput` contract** — optional interface enabling JSON schema-constrained output with `outputSchema()` and `mapOutput()` methods; activates structured mode in `RunAgentAction`.
- **`HasTools` contract** — optional interface for exposing Laravel AI SDK Tool instances to the model via `tools()`.
- **`HasStreamingResponse` contract** — optional interface enabling streaming execution with per-chunk `onChunk()` callbacks and a final `onComplete()` callback.
- **`AgentContext` DTO** — immutable `final readonly` context object carrying the Eloquent record(s), arbitrary metadata, user instruction, panel ID, and resource class for an invocation. Provides `fromRecord()`, `fromRecords()`, and `withMeta()` factory/mutation methods.
- **`AgentResult` DTO** — immutable `final readonly` value object wrapping the AI response: raw text, output format, structured value, input/output token counts, provider, model, and provider metadata. Provides `isStructured()` and `toArray()`.
- **`ActionMode` enum** — `Sync`, `Queued`, and `Streaming` cases describing how an action should be dispatched.
- **`OutputFormat` enum** — `Text`, `Structured`, and `Markdown` cases describing the format of an agent result.
- **`AgentException`** — runtime exception wrapping AI provider failures; includes `fromThrowable()` factory and `getAgentClass()` accessor.
- **`InvalidContextException`** — argument exception surfaced when an `AgentContext` is missing required data; includes `missingRecord()` and `missingMeta()` named constructors.
- **`InteractsWithAgent` trait** — default `provider()` and `model()` implementations delegating to `config('ai-action.provider')` and `config('ai-action.model')`, reducing boilerplate in agent classes.
- **`InteractsWithContext` trait** — helper methods for inspecting `AgentContext` inside an agent: `requireRecord()`, `requireMeta()`, `meta()`, and `hasRecordOf()`.
- **`RunAgentAction`** — injectable, non-abstract orchestrator class with a single `execute(AgentAction, AgentContext): AgentResult` method. Automatically selects the structured, streaming, or plain-text execution branch based on the agent's implemented interfaces. Wraps all provider failures as `AgentException`. Logs invocations when `ai-action.logging` is enabled.
- **`RunAgentActionJob`** — `ShouldQueue` + `ShouldBeUnique` job that wraps a `RunAgentAction::execute()` call for background processing. Derives its uniqueness key from the agent class name and a hash of the serialised context. Respects the `ai-action.queue` config key.
- **`FakeAgentAction`** — test double extending `RunAgentAction`. Provides `fakeResponse()` to register pre-canned results per agent class, `assertAgentCalled()` and `assertAgentNotCalled()` for invocation-count assertions, and `reset()` for test isolation. Binds itself into the service container so no real API calls are made during tests.
- **`AgentActionAssertions`** — fluent assertion wrapper for `AgentResult` objects. Methods: `assertText()`, `assertTextContains()`, `assertIsStructured()`, `assertIsText()`, `assertStructured()`, `assertProvider()`, `assertModel()`, `assertInputTokens()`, `assertOutputTokens()`.
- **`AgentAction` facade** — static proxy to the `RunAgentAction` singleton bound in the container.
- **`LaravelAiActionServiceProvider`** — Spatie `PackageServiceProvider` that registers the `ai-action` config file, binds `RunAgentAction` as a singleton, and registers the `make:ai-action` Artisan command.
- **`make:ai-action` Artisan command** — generates `app/Ai/Actions/{Name}.php` from `stubs/action.stub`, resolving the published stub first and falling back to the package bundled stub.
- **`config/ai-action.php`** — package configuration exposing `provider`, `model`, `queue`, `max_tokens`, and `logging` keys, each overridable via environment variables.

## [1.0.3](https://github.com/pixelworxio/laravel-ai-action/compare/1.0.2...1.0.3) - 2026-04-26

### What's Changed

* chore(deps): bump laravel/framework from 12.52.0 to 12.53.0 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/4
* chore(deps): bump laravel/ai from 0.2.1 to 0.2.5 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/5
* chore(deps-dev): bump larastan/larastan from 3.9.2 to 3.9.3 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/6
* chore(deps): bump laravel/ai from 0.2.5 to 0.2.6 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/7
* chore(deps-dev): bump pestphp/pest from 4.4.1 to 4.4.2 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/8
* chore(deps-dev): bump laravel/pint from 1.27.1 to 1.29.0 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/9
* chore(deps): bump laravel/ai from 0.2.6 to 0.3.0 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/10
* chore(deps): bump laravel/framework from 12.53.0 to 12.54.1 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/11
* chore(deps-dev): bump pestphp/pest from 4.4.2 to 4.4.3 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/13
* chore(deps): bump laravel/ai from 0.3.0 to 0.3.2 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/14
* Add Laravel 13 support by @whoisthisstud in https://github.com/pixelworxio/laravel-ai-action/pull/15
* chore(deps): bump ramsey/composer-install from 3 to 4 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/12
* chore(deps): bump laravel/ai from 0.3.2 to 0.4.2 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/18
* chore(deps): bump laravel/ai from 0.4.2 to 0.4.3 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/19
* chore(deps-dev): bump pestphp/pest from 4.4.3 to 4.4.5 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/21
* chore(deps): bump laravel/ai from 0.4.3 to 0.5.1 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/22
* chore(deps-dev): bump pestphp/pest-plugin-arch from 4.0.0 to 4.0.2 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/24
* chore(deps-dev): bump nunomaduro/collision from 8.9.2 to 8.9.3 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/26
* chore(deps-dev): bump larastan/larastan from 3.9.3 to 3.9.4 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/27
* chore(deps-dev): bump pestphp/pest from 4.4.5 to 4.5.0 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/25
* chore(deps-dev): bump pestphp/pest from 4.5.0 to 4.6.3 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/28
* chore(deps): bump laravel/ai from 0.5.1 to 0.6.0 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/29
* chore(deps-dev): bump larastan/larastan from 3.9.4 to 3.9.6 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/30
* chore(deps-dev): bump nunomaduro/collision from 8.9.3 to 8.9.4 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/32
* chore(deps): bump laravel/ai from 0.6.0 to 0.6.3 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/33
* chore(deps-dev): bump laravel/pint from 1.29.0 to 1.29.1 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/34
* chore(deps): bump dependabot/fetch-metadata from 2.5.0 to 3.1.0 by @dependabot[bot] in https://github.com/pixelworxio/laravel-ai-action/pull/31

**Full Changelog**: https://github.com/pixelworxio/laravel-ai-action/compare/1.0.2...1.0.3

## [1.0.2](https://github.com/pixelworxio/laravel-ai-action/compare/1.0.1...1.0.2) - 2026-02-23

### What's Changed

* touch ups by @whoisthisstud in https://github.com/pixelworxio/laravel-ai-action/pull/3

**Full Changelog**: https://github.com/pixelworxio/laravel-ai-action/compare/1.0.1...1.0.2

## [1.0.1](https://github.com/pixelworxio/laravel-ai-action/compare/v1.0.0...1.0.1) - 2026-02-23

### What's Changed

* Enforce StructuredAgentResponse type by @whoisthisstud in https://github.com/pixelworxio/laravel-ai-action/pull/2

### New Contributors

* @whoisthisstud made their first contribution in https://github.com/pixelworxio/laravel-ai-action/pull/2

**Full Changelog**: https://github.com/pixelworxio/laravel-ai-action/compare/1.0.0...1.0.1
