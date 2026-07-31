<?php

declare(strict_types=1);

use Illuminate\Support\Sleep;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;
use Pixelworxio\LaravelAiAction\Exceptions\AgentException;
use Pixelworxio\LaravelAiAction\Middleware\RetryAgentCall;

function retryTestAgent(): AgentAction
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

function retryTestResult(): AgentResult
{
    return new AgentResult(
        text: 'ok',
        format: OutputFormat::Text,
        structured: null,
        inputTokens: 1,
        outputTokens: 1,
        provider: 'anthropic',
        model: 'claude-sonnet-4-20250514',
        metadata: [],
    );
}

describe('RetryAgentCall', function (): void {
    it('returns the result immediately on first success without sleeping', function (): void {
        Sleep::fake();

        $middleware = new RetryAgentCall(times: 3);
        $calls = 0;

        $result = $middleware->handle(
            retryTestAgent(),
            AgentContext::fromRecords([]),
            function () use (&$calls): AgentResult {
                $calls++;

                return retryTestResult();
            },
        );

        expect($result)->toBeInstanceOf(AgentResult::class)
            ->and($result->text)->toBe('ok')
            ->and($calls)->toBe(1);

        Sleep::assertNeverSlept();
    });

    it('retries after an AgentException and eventually succeeds', function (): void {
        Sleep::fake();

        $middleware = new RetryAgentCall(times: 3, backoffSeconds: [1, 2]);
        $attempts = 0;

        $result = $middleware->handle(
            retryTestAgent(),
            AgentContext::fromRecords([]),
            function () use (&$attempts): AgentResult {
                $attempts++;

                if ($attempts < 3) {
                    throw new AgentException(retryTestAgent(), "attempt {$attempts} failed");
                }

                return retryTestResult();
            },
        );

        expect($attempts)->toBe(3)
            ->and($result->text)->toBe('ok');

        Sleep::assertSequence([
            Sleep::for(1)->seconds(),
            Sleep::for(2)->seconds(),
        ]);
    });

    it('throws the last AgentException once attempts are exhausted', function (): void {
        Sleep::fake();

        $middleware = new RetryAgentCall(times: 2, backoffSeconds: [1]);
        $attempts = 0;

        $act = function () use ($middleware, &$attempts): AgentResult {
            return $middleware->handle(
                retryTestAgent(),
                AgentContext::fromRecords([]),
                function () use (&$attempts): AgentResult {
                    $attempts++;

                    throw new AgentException(retryTestAgent(), "attempt {$attempts} failed");
                },
            );
        };

        expect($act)->toThrow(AgentException::class, 'attempt 2 failed')
            ->and($attempts)->toBe(2);
    });

    it('does not catch exceptions other than AgentException', function (): void {
        $middleware = new RetryAgentCall(times: 3);

        $act = fn (): AgentResult => $middleware->handle(
            retryTestAgent(),
            AgentContext::fromRecords([]),
            function (): AgentResult {
                throw new RuntimeException('not an AgentException');
            },
        );

        expect($act)->toThrow(RuntimeException::class, 'not an AgentException');
    });

    it('repeats the final backoff value for attempts beyond the configured list', function (): void {
        Sleep::fake();

        $middleware = new RetryAgentCall(times: 4, backoffSeconds: [1]);
        $attempts = 0;

        $middleware->handle(
            retryTestAgent(),
            AgentContext::fromRecords([]),
            function () use (&$attempts): AgentResult {
                $attempts++;

                if ($attempts < 4) {
                    throw new AgentException(retryTestAgent(), 'fail');
                }

                return retryTestResult();
            },
        );

        Sleep::assertSequence([
            Sleep::for(1)->seconds(),
            Sleep::for(1)->seconds(),
            Sleep::for(1)->seconds(),
        ]);
    });
});
