<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;
use Pixelworxio\LaravelAiAction\Middleware\Idempotent;

function idempotentTestAgent(): AgentAction
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

function idempotentTestResult(string $text = 'first'): AgentResult
{
    return new AgentResult(
        text: $text,
        format: OutputFormat::Text,
        structured: null,
        inputTokens: 1,
        outputTokens: 1,
        provider: 'anthropic',
        model: 'claude-sonnet-4-20250514',
        metadata: [],
    );
}

describe('Idempotent', function (): void {
    it('calls through and caches the result on a cache miss', function (): void {
        $middleware = new Idempotent(ttl: 3600);
        $calls = 0;

        $result = $middleware->handle(
            idempotentTestAgent(),
            AgentContext::fromRecords([]),
            function () use (&$calls): AgentResult {
                $calls++;

                return idempotentTestResult();
            },
        );

        expect($calls)->toBe(1)
            ->and($result->text)->toBe('first');
    });

    it('returns the cached result on a repeat call with the same context, without calling through again', function (): void {
        $middleware = new Idempotent(ttl: 3600);
        $agent = idempotentTestAgent();
        $context = AgentContext::fromRecords([], ['key' => 'value']);
        $calls = 0;

        $next = function () use (&$calls): AgentResult {
            $calls++;

            return idempotentTestResult((string) $calls);
        };

        $first = $middleware->handle($agent, $context, $next);
        $second = $middleware->handle($agent, $context, $next);

        expect($calls)->toBe(1)
            ->and($second->text)->toBe($first->text)
            ->and($second->text)->toBe('1');
    });

    it('treats different meta as a different cache key', function (): void {
        $middleware = new Idempotent(ttl: 3600);
        $agent = idempotentTestAgent();
        $calls = 0;

        $next = function () use (&$calls): AgentResult {
            $calls++;

            return idempotentTestResult((string) $calls);
        };

        $middleware->handle($agent, AgentContext::fromRecords([], ['id' => 1]), $next);
        $middleware->handle($agent, AgentContext::fromRecords([], ['id' => 2]), $next);

        expect($calls)->toBe(2);
    });

    it('keys on the record identity rather than the whole model', function (): void {
        $middleware = new Idempotent(ttl: 3600);
        $agent = idempotentTestAgent();
        $calls = 0;

        $next = function () use (&$calls): AgentResult {
            $calls++;

            return idempotentTestResult((string) $calls);
        };

        $modelA = Mockery::mock(Model::class);
        $modelA->shouldReceive('getKey')->andReturn(42);

        $modelB = Mockery::mock(Model::class);
        $modelB->shouldReceive('getKey')->andReturn(42);

        $middleware->handle($agent, AgentContext::fromRecord($modelA), $next);
        $middleware->handle($agent, AgentContext::fromRecord($modelB), $next);

        expect($calls)->toBe(1);
    });

    it('supports an explicit cache key', function (): void {
        $middlewareA = new Idempotent(ttl: 3600, key: 'shared-key');
        $middlewareB = new Idempotent(ttl: 3600, key: 'shared-key');
        $agent = idempotentTestAgent();
        $calls = 0;

        $next = function () use (&$calls): AgentResult {
            $calls++;

            return idempotentTestResult((string) $calls);
        };

        $middlewareA->handle($agent, AgentContext::fromRecords([], ['x' => 1]), $next);
        $middlewareB->handle($agent, AgentContext::fromRecords([], ['x' => 'totally different']), $next);

        expect($calls)->toBe(1);
    });
});
