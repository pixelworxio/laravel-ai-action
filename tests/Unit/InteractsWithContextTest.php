<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Pixelworxio\LaravelAiAction\Concerns\InteractsWithContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\Exceptions\InvalidContextException;

// Concrete class that uses the trait so we can call protected methods.
class ContextInspector
{
    use InteractsWithContext;

    public function callHasRecord(AgentContext $c): bool
    {
        return $this->hasRecord($c);
    }

    public function callHasRecords(AgentContext $c): bool
    {
        return $this->hasRecords($c);
    }

    public function callHasMeta(string $key, AgentContext $c): bool
    {
        return $this->hasMeta($key, $c);
    }

    public function callGetMeta(string $key, AgentContext $c, mixed $default = null): mixed
    {
        return $this->getMeta($key, $c, $default);
    }

    public function callGetRecord(AgentContext $c): ?Model
    {
        return $this->getRecord($c);
    }

    public function callRequireRecord(AgentContext $c): Model
    {
        return $this->requireRecord($c);
    }

    public function callRequireMeta(string $key, AgentContext $c): mixed
    {
        return $this->requireMeta($key, $c);
    }
}

describe('InteractsWithContext', function (): void {
    beforeEach(function (): void {
        $this->inspector = new ContextInspector;
    });

    describe('hasRecord()', function (): void {
        it('returns true when context has a record', function (): void {
            $record = Mockery::mock(Model::class);
            $ctx = AgentContext::fromRecord($record);

            expect($this->inspector->callHasRecord($ctx))->toBeTrue();
        });

        it('returns false when context has no record', function (): void {
            $ctx = AgentContext::fromRecords([]);

            expect($this->inspector->callHasRecord($ctx))->toBeFalse();
        });
    });

    describe('hasRecords()', function (): void {
        it('returns true when context has records', function (): void {
            $ctx = AgentContext::fromRecords([Mockery::mock(Model::class)]);

            expect($this->inspector->callHasRecords($ctx))->toBeTrue();
        });

        it('returns false when records array is empty', function (): void {
            $ctx = AgentContext::fromRecords([]);

            expect($this->inspector->callHasRecords($ctx))->toBeFalse();
        });
    });

    describe('hasMeta()', function (): void {
        it('returns true when key exists', function (): void {
            $ctx = AgentContext::fromRecords([], ['topic' => 'sales']);

            expect($this->inspector->callHasMeta('topic', $ctx))->toBeTrue();
        });

        it('returns false when key is absent', function (): void {
            $ctx = AgentContext::fromRecords([]);

            expect($this->inspector->callHasMeta('topic', $ctx))->toBeFalse();
        });
    });

    describe('getMeta()', function (): void {
        it('returns the value for an existing key', function (): void {
            $ctx = AgentContext::fromRecords([], ['locale' => 'en']);

            expect($this->inspector->callGetMeta('locale', $ctx))->toBe('en');
        });

        it('returns null by default when key is absent', function (): void {
            $ctx = AgentContext::fromRecords([]);

            expect($this->inspector->callGetMeta('missing', $ctx))->toBeNull();
        });

        it('returns the provided default when key is absent', function (): void {
            $ctx = AgentContext::fromRecords([]);

            expect($this->inspector->callGetMeta('missing', $ctx, 'fallback'))->toBe('fallback');
        });
    });

    describe('getRecord()', function (): void {
        it('returns the record when present', function (): void {
            $record = Mockery::mock(Model::class);
            $ctx = AgentContext::fromRecord($record);

            expect($this->inspector->callGetRecord($ctx))->toBe($record);
        });

        it('returns null when no record is set', function (): void {
            $ctx = AgentContext::fromRecords([]);

            expect($this->inspector->callGetRecord($ctx))->toBeNull();
        });
    });

    describe('requireRecord()', function (): void {
        it('returns the record when one is present', function (): void {
            $record = Mockery::mock(Model::class);
            $ctx = AgentContext::fromRecord($record);

            expect($this->inspector->callRequireRecord($ctx))->toBe($record);
        });

        it('throws InvalidContextException when no record is set', function (): void {
            $ctx = AgentContext::fromRecords([]);

            expect(fn () => $this->inspector->callRequireRecord($ctx))
                ->toThrow(InvalidContextException::class);
        });
    });

    describe('requireMeta()', function (): void {
        it('returns the value when key exists', function (): void {
            $ctx = AgentContext::fromRecords([], ['user_id' => 42]);

            expect($this->inspector->callRequireMeta('user_id', $ctx))->toBe(42);
        });

        it('throws InvalidContextException when key is absent', function (): void {
            $ctx = AgentContext::fromRecords([]);

            expect(fn () => $this->inspector->callRequireMeta('missing_key', $ctx))
                ->toThrow(InvalidContextException::class);
        });

        it('thrown exception message includes the missing key name', function (): void {
            $ctx = AgentContext::fromRecords([]);

            expect(fn () => $this->inspector->callRequireMeta('tenant_id', $ctx))
                ->toThrow(InvalidContextException::class, 'tenant_id');
        });
    });
});
