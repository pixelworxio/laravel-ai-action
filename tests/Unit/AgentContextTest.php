<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\Exceptions\InvalidContextException;

describe('AgentContext', function (): void {
    describe('fromRecord()', function (): void {
        it('sets the record and leaves records empty', function (): void {
            $model = Mockery::mock(Model::class);

            $context = AgentContext::fromRecord($model);

            expect($context->record)->toBe($model)
                ->and($context->records)->toBeEmpty()
                ->and($context->meta)->toBeEmpty()
                ->and($context->userInstruction)->toBeNull()
                ->and($context->panelId)->toBeNull()
                ->and($context->resourceClass)->toBeNull();
        });

        it('accepts initial meta', function (): void {
            $model = Mockery::mock(Model::class);
            $meta = ['key' => 'value'];

            $context = AgentContext::fromRecord($model, $meta);

            expect($context->meta)->toBe($meta);
        });
    });

    describe('fromRecords()', function (): void {
        it('sets records and leaves record null', function (): void {
            $models = [Mockery::mock(Model::class), Mockery::mock(Model::class)];

            $context = AgentContext::fromRecords($models);

            expect($context->record)->toBeNull()
                ->and($context->records)->toBe($models)
                ->and($context->meta)->toBeEmpty();
        });

        it('accepts initial meta', function (): void {
            $context = AgentContext::fromRecords([], ['foo' => 'bar']);

            expect($context->meta)->toBe(['foo' => 'bar']);
        });
    });

    describe('withMeta()', function (): void {
        it('returns a new instance with the additional key', function (): void {
            $original = AgentContext::fromRecords([], ['a' => 1]);

            $updated = $original->withMeta('b', 2);

            expect($updated)->not->toBe($original)
                ->and($updated->meta)->toBe(['a' => 1, 'b' => 2])
                ->and($original->meta)->toBe(['a' => 1]);
        });

        it('overwrites an existing key immutably', function (): void {
            $original = AgentContext::fromRecords([], ['a' => 1]);

            $updated = $original->withMeta('a', 99);

            expect($updated->meta['a'])->toBe(99)
                ->and($original->meta['a'])->toBe(1);
        });

        it('preserves all other context properties', function (): void {
            $model = Mockery::mock(Model::class);
            $original = AgentContext::fromRecord($model, ['x' => 'y']);

            $updated = $original->withMeta('z', 'w');

            expect($updated->record)->toBe($model)
                ->and($updated->records)->toBeEmpty()
                ->and($updated->userInstruction)->toBeNull();
        });
    });

    describe('readonly enforcement', function (): void {
        it('is a readonly final class', function (): void {
            $reflection = new ReflectionClass(AgentContext::class);

            expect($reflection->isFinal())->toBeTrue()
                ->and($reflection->isReadOnly())->toBeTrue();
        });
    });
});
