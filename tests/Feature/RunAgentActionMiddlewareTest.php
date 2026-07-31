<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Laravel\Ai\AiServiceProvider;
use Laravel\Ai\AnonymousAgent;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Contracts\HasMiddleware;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Events\AgentActionCompleted;
use Pixelworxio\LaravelAiAction\Middleware\FallbackProvider;
use Pixelworxio\LaravelAiAction\Middleware\Idempotent;

function setupMiddlewareTestAiConfig(): void
{
    config([
        'ai.default' => 'anthropic',
        'ai.providers.anthropic' => ['driver' => 'anthropic', 'key' => 'fake-key', 'name' => 'anthropic'],
        'ai.providers.openai' => ['driver' => 'openai', 'key' => 'fake-key', 'name' => 'openai'],
    ]);
}

function makeIdempotentAgent(int &$promptCalls): AgentAction
{
    return new class($promptCalls) implements AgentAction, HasMiddleware
    {
        public function __construct(private int &$promptCalls) {}

        public function instructions(AgentContext $context): string
        {
            return 'You help with testing.';
        }

        public function prompt(AgentContext $context): string
        {
            $this->promptCalls++;

            return 'Say hello.';
        }

        public function provider(): string
        {
            return 'anthropic';
        }

        public function model(): string
        {
            return 'claude-sonnet-4-20250514';
        }

        public function middleware(): array
        {
            return [new Idempotent(ttl: 3600)];
        }

        public function handle(AgentContext $context): AgentResult
        {
            return app(RunAgentAction::class)->execute($this, $context);
        }
    };
}

function makeFallbackAgent(): AgentAction
{
    return new class implements AgentAction, HasMiddleware
    {
        public function instructions(AgentContext $context): string
        {
            return 'You help with testing.';
        }

        public function prompt(AgentContext $context): string
        {
            return 'Say hello.';
        }

        public function provider(): string
        {
            return 'anthropic';
        }

        public function model(): string
        {
            return 'claude-sonnet-4-20250514';
        }

        public function middleware(): array
        {
            return [new FallbackProvider(['openai'])];
        }

        public function handle(AgentContext $context): AgentResult
        {
            return app(RunAgentAction::class)->execute($this, $context);
        }
    };
}

describe('RunAgentAction middleware wiring', function (): void {
    beforeEach(function (): void {
        $this->app->register(AiServiceProvider::class);
        setupMiddlewareTestAiConfig();
    });

    it('short-circuits a repeat call through Idempotent without calling the provider again', function (): void {
        AnonymousAgent::fake(['Hello from fake']);
        $promptCalls = 0;
        $agent = makeIdempotentAgent($promptCalls);
        $context = AgentContext::fromRecords([], ['key' => 'value']);

        $first = app(RunAgentAction::class)->execute($agent, $context);
        $second = app(RunAgentAction::class)->execute($agent, $context);

        expect($promptCalls)->toBe(1)
            ->and($second->text)->toBe($first->text);
    });

    it('routes through FallbackProvider when the primary provider is not configured to fail', function (): void {
        AnonymousAgent::fake(['Hello from fake']);

        $result = app(RunAgentAction::class)->execute(makeFallbackAgent(), AgentContext::fromRecords([]));

        expect($result->provider)->toBe('anthropic');
    });

    it('dispatches AgentActionCompleted with the agent class, result, and a duration', function (): void {
        Event::fake();
        AnonymousAgent::fake(['Hello from fake']);
        $promptCalls = 0;

        $agent = makeIdempotentAgent($promptCalls);

        app(RunAgentAction::class)->execute($agent, AgentContext::fromRecords([]));

        Event::assertDispatched(AgentActionCompleted::class, function (AgentActionCompleted $event) use ($agent): bool {
            return $event->agentClass === $agent::class
                && $event->result->text === 'Hello from fake'
                && $event->durationMs >= 0.0;
        });
    });
});
