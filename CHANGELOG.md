# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - unreleased

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

[Unreleased]: https://github.com/pixelworxio/laravel-ai-action/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/pixelworxio/laravel-ai-action/releases/tag/v1.0.0
