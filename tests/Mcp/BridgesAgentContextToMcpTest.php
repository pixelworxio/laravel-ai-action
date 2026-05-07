<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\AssertionFailedError;
use Pixelworxio\LaravelAiAction\Exceptions\InvalidContextException;
use Pixelworxio\LaravelAiAction\Mcp\Concerns\BridgesAgentContextToMcp;
use Pixelworxio\LaravelAiAction\Tests\McpTestCase;

uses(McpTestCase::class)->group('mcp');

// A thin concrete class exposing the trait's protected methods.
class BridgeTester
{
    use BridgesAgentContextToMcp;

    public function testResolveRecord(string $modelClass, int|string $id, mixed $user): Model
    {
        return $this->resolveRecord($modelClass, $id, $user);
    }

    public function testResolveRecords(string $modelClass, array $ids, mixed $user): array
    {
        return $this->resolveRecords($modelClass, $ids, $user);
    }

    public function testMetaFromInput(array $input, array $keys): array
    {
        return $this->metaFromInput($input, $keys);
    }
}

// ---------------------------------------------------------------------------
// Minimal in-memory Eloquent model for testing
// ---------------------------------------------------------------------------

class StubBridgeModel extends Model
{
    protected $table = 'stub_bridge_models';

    protected $guarded = [];

    public $timestamps = false;

    // Allow resolving via findOrFail / findMany without a real DB.
    private static array $fakeStore = [];

    public static function seedFake(int $id): void
    {
        static::$fakeStore[$id] = ['id' => $id, 'name' => "item-{$id}"];
    }

    public static function clearFake(): void
    {
        static::$fakeStore = [];
    }

    public static function findOrFail($id, $columns = ['*']): static
    {
        if (! isset(static::$fakeStore[$id])) {
            throw new ModelNotFoundException;
        }

        $instance = new static(static::$fakeStore[$id]);
        $instance->setAttribute('id', $id);

        return $instance;
    }

    public static function findMany($ids, $columns = ['*']): EloquentCollection
    {
        $results = new EloquentCollection;

        foreach ((array) $ids as $id) {
            if (isset(static::$fakeStore[$id])) {
                $instance = new static(static::$fakeStore[$id]);
                $instance->setAttribute('id', $id);
                $results->push($instance);
            }
        }

        return $results;
    }
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('BridgesAgentContextToMcp', function (): void {
    beforeEach(function (): void {
        $this->tester = new BridgeTester;
        StubBridgeModel::clearFake();
    });

    describe('resolveRecord()', function (): void {
        it('returns the model when it exists', function (): void {
            StubBridgeModel::seedFake(1);

            $model = $this->tester->testResolveRecord(StubBridgeModel::class, 1, null);

            expect($model)->toBeInstanceOf(StubBridgeModel::class)
                ->and($model->getAttribute('id'))->toBe(1);
        });

        it('throws InvalidContextException when the record does not exist', function (): void {
            expect(fn () => $this->tester->testResolveRecord(StubBridgeModel::class, 999, null))
                ->toThrow(InvalidContextException::class);
        });

        it('exception message includes the model base name and id', function (): void {
            try {
                $this->tester->testResolveRecord(StubBridgeModel::class, 42, null);
            } catch (InvalidContextException $e) {
                expect($e->getMessage())->toContain('StubBridgeModel')
                    ->and($e->getMessage())->toContain('42');

                return;
            }

            throw new AssertionFailedError('Expected InvalidContextException was not thrown.');
        });
    });

    describe('resolveRecords()', function (): void {
        it('returns matching models for the given ids', function (): void {
            StubBridgeModel::seedFake(1);
            StubBridgeModel::seedFake(2);

            $models = $this->tester->testResolveRecords(StubBridgeModel::class, [1, 2], null);

            expect($models)->toHaveCount(2);
        });

        it('silently omits ids that do not exist', function (): void {
            StubBridgeModel::seedFake(1);

            $models = $this->tester->testResolveRecords(StubBridgeModel::class, [1, 999], null);

            expect($models)->toHaveCount(1);
        });

        it('returns an empty array when no ids match', function (): void {
            $models = $this->tester->testResolveRecords(StubBridgeModel::class, [99, 100], null);

            expect($models)->toBeEmpty();
        });
    });

    describe('metaFromInput()', function (): void {
        it('extracts only the requested keys', function (): void {
            $input = ['prompt' => 'Hello', 'locale' => 'en', 'extra' => 'ignored'];

            $meta = $this->tester->testMetaFromInput($input, ['prompt', 'locale']);

            expect($meta)->toBe(['prompt' => 'Hello', 'locale' => 'en']);
        });

        it('silently omits keys absent from input', function (): void {
            $meta = $this->tester->testMetaFromInput(['a' => 1], ['a', 'b']);

            expect($meta)->toBe(['a' => 1]);
        });

        it('returns an empty array when no keys match', function (): void {
            $meta = $this->tester->testMetaFromInput(['x' => 1], ['y', 'z']);

            expect($meta)->toBeEmpty();
        });
    });
});
