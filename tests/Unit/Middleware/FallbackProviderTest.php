<?php

declare(strict_types=1);

use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;
use Pixelworxio\LaravelAiAction\Exceptions\AgentException;
use Pixelworxio\LaravelAiAction\Middleware\FallbackProvider;

function fallbackTestAgent(): AgentAction
{
    return new class implements AgentAction
    {
        public function instructions(AgentContext $context): string
        {
            return '';
        }

        public function prompt(AgentContext $context): string
        {
            return '';
        }

        public function provider(): string
        {
            return 'anthropic';
        }

        public function model(): string
        {
            return 'claude-sonnet-4-20250514';
        }

        public function handle(AgentContext $context): AgentResult
        {
            throw new RuntimeException('not used in this test');
        }
    };
}

function fallbackTestResult(string $provider, string $model): AgentResult
{
    return new AgentResult(
        text: 'ok',
        format: OutputFormat::Text,
        structured: null,
        inputTokens: 1,
        outputTokens: 1,
        provider: $provider,
        model: $model,
        metadata: [],
    );
}

describe('FallbackProvider', function (): void {
    it('does not override the context when the agents own provider succeeds', function (): void {
        $middleware = new FallbackProvider(['openai']);
        $agent = fallbackTestAgent();
        $seenContexts = [];

        $result = $middleware->handle(
            $agent,
            AgentContext::fromRecords([]),
            function (AgentAction $agent, AgentContext $context) use (&$seenContexts): AgentResult {
                $seenContexts[] = $context;

                return fallbackTestResult($agent->provider(), $agent->model());
            },
        );

        expect($seenContexts)->toHaveCount(1)
            ->and($seenContexts[0]->providerOverride)->toBeNull()
            ->and($result->provider)->toBe('anthropic');
    });

    it('falls back to the next provider after the first attempt fails', function (): void {
        $middleware = new FallbackProvider(['openai']);
        $agent = fallbackTestAgent();
        $attempts = [];

        $result = $middleware->handle(
            $agent,
            AgentContext::fromRecords([]),
            function (AgentAction $agent, AgentContext $context) use (&$attempts): AgentResult {
                $provider = $context->providerOverride ?? $agent->provider();
                $attempts[] = $provider;

                if ($provider === 'anthropic') {
                    throw new AgentException($agent, 'anthropic is down');
                }

                return fallbackTestResult($provider, $context->modelOverride ?? $agent->model());
            },
        );

        expect($attempts)->toBe(['anthropic', 'openai'])
            ->and($result->provider)->toBe('openai');
    });

    it('supports a fallback model alongside the fallback provider', function (): void {
        $middleware = new FallbackProvider([
            ['provider' => 'openai', 'model' => 'gpt-4o-mini'],
        ]);
        $agent = fallbackTestAgent();

        $result = $middleware->handle(
            $agent,
            AgentContext::fromRecords([]),
            function (AgentAction $agent, AgentContext $context): AgentResult {
                if (($context->providerOverride ?? $agent->provider()) === 'anthropic') {
                    throw new AgentException($agent, 'anthropic is down');
                }

                return fallbackTestResult($context->providerOverride, $context->modelOverride);
            },
        );

        expect($result->provider)->toBe('openai')
            ->and($result->model)->toBe('gpt-4o-mini');
    });

    it('throws the last exception once every provider has failed', function (): void {
        $middleware = new FallbackProvider(['openai']);
        $agent = fallbackTestAgent();

        $act = fn (): AgentResult => $middleware->handle(
            $agent,
            AgentContext::fromRecords([]),
            function (AgentAction $agent, AgentContext $context): AgentResult {
                $provider = $context->providerOverride ?? $agent->provider();

                throw new AgentException($agent, "{$provider} is down");
            },
        );

        expect($act)->toThrow(AgentException::class, 'openai is down');
    });
});
