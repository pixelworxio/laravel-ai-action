<?php

declare(strict_types=1);

use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\Exceptions\InvalidContextException;

function makeEmptyContext(): AgentContext
{
    return AgentContext::fromRecords([], []);
}

describe('InvalidContextException', function (): void {
    it('getContext() returns the context that was passed in', function (): void {
        $context = makeEmptyContext();
        $e = new InvalidContextException($context, 'bad context');

        expect($e->getContext())->toBe($context);
    });

    it('uses the provided message', function (): void {
        $e = new InvalidContextException(makeEmptyContext(), 'specific message');

        expect($e->getMessage())->toBe('specific message');
    });

    it('uses a default message when none is provided', function (): void {
        $e = new InvalidContextException(makeEmptyContext());

        expect($e->getMessage())->toContain('AgentContext');
    });

    it('accepts code and previous exception', function (): void {
        $previous = new RuntimeException('cause');
        $e = new InvalidContextException(makeEmptyContext(), 'msg', 5, $previous);

        expect($e->getCode())->toBe(5)
            ->and($e->getPrevious())->toBe($previous);
    });

    it('missingRecord() creates exception with record-specific message', function (): void {
        $context = makeEmptyContext();
        $e = InvalidContextException::missingRecord($context);

        expect($e)->toBeInstanceOf(InvalidContextException::class)
            ->and($e->getContext())->toBe($context)
            ->and($e->getMessage())->toContain('record');
    });

    it('missingMeta() creates exception with key name in message', function (): void {
        $context = makeEmptyContext();
        $e = InvalidContextException::missingMeta($context, 'user_id');

        expect($e)->toBeInstanceOf(InvalidContextException::class)
            ->and($e->getContext())->toBe($context)
            ->and($e->getMessage())->toContain('user_id');
    });
});
