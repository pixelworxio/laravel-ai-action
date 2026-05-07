<?php

declare(strict_types=1);

use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;
use Pixelworxio\LaravelAiAction\Exceptions\AgentException;

function makeStubAgent(): AgentAction
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
            return new AgentResult('', OutputFormat::Text, null, 0, 0, 'anthropic', 'claude-sonnet-4-20250514', []);
        }
    };
}

describe('AgentException', function (): void {
    it('stores the agent class name', function (): void {
        $agent = makeStubAgent();
        $e = new AgentException($agent, 'Something went wrong');

        expect($e->getAgentClass())->toBe($agent::class);
    });

    it('uses provided message', function (): void {
        $agent = makeStubAgent();
        $e = new AgentException($agent, 'Custom message');

        expect($e->getMessage())->toBe('Custom message');
    });

    it('generates default message when none is provided', function (): void {
        $agent = makeStubAgent();
        $e = new AgentException($agent);

        expect($e->getMessage())->toContain($agent::class);
    });

    it('accepts code and previous', function (): void {
        $agent = makeStubAgent();
        $previous = new RuntimeException('root cause', 42);
        $e = new AgentException($agent, 'wrap', 42, $previous);

        expect($e->getCode())->toBe(42)
            ->and($e->getPrevious())->toBe($previous);
    });

    it('fromThrowable() wraps cause with class name and message', function (): void {
        $agent = makeStubAgent();
        $cause = new RuntimeException('network timeout', 99);

        $e = AgentException::fromThrowable($agent, $cause);

        expect($e)->toBeInstanceOf(AgentException::class)
            ->and($e->getAgentClass())->toBe($agent::class)
            ->and($e->getMessage())->toContain($agent::class)
            ->and($e->getMessage())->toContain('network timeout')
            ->and($e->getCode())->toBe(99)
            ->and($e->getPrevious())->toBe($cause);
    });
});
