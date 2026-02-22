<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Concerns;

use Illuminate\Database\Eloquent\Model;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\Exceptions\InvalidContextException;

/**
 * Provides helper methods for reading and asserting AgentContext contents.
 *
 * Mix this trait into any agent action class that needs to inspect the runtime
 * context in a consistent, expressive way without repeating the same guard
 * clauses in instructions() and prompt(). Every method accepts AgentContext
 * as a parameter — no $context property is assumed on the using class.
 */
trait InteractsWithContext
{
    /**
     * Determine whether the context contains a non-null primary record.
     *
     * @param AgentContext $context The context to inspect.
     * @return bool True when $context->record is not null.
     */
    protected function hasRecord(AgentContext $context): bool
    {
        return $context->record !== null;
    }

    /**
     * Determine whether the context contains at least one record in the batch.
     *
     * @param AgentContext $context The context to inspect.
     * @return bool True when $context->records is non-empty.
     */
    protected function hasRecords(AgentContext $context): bool
    {
        return count($context->records) > 0;
    }

    /**
     * Determine whether the context meta array contains the given key.
     *
     * @param string       $key     The metadata key to check.
     * @param AgentContext $context The context to inspect.
     * @return bool True when $key exists in $context->meta.
     */
    protected function hasMeta(string $key, AgentContext $context): bool
    {
        return array_key_exists($key, $context->meta);
    }

    /**
     * Retrieve a metadata value, returning $default when the key is absent.
     *
     * @param string       $key     The metadata key to retrieve.
     * @param AgentContext $context The context to inspect.
     * @param mixed        $default The value returned when the key does not exist.
     * @return mixed The metadata value or $default.
     */
    protected function getMeta(string $key, AgentContext $context, mixed $default = null): mixed
    {
        return $context->meta[$key] ?? $default;
    }

    /**
     * Return the primary record from the context, or null if none is set.
     *
     * @param AgentContext $context The context to inspect.
     * @return Model|null The primary Eloquent record, or null.
     */
    protected function getRecord(AgentContext $context): ?Model
    {
        return $context->record;
    }

    /**
     * Assert that the context contains a non-null record and return it.
     *
     * Prefer this over getRecord() inside instructions() and prompt() when the
     * agent unconditionally requires a record — it surfaces a clear exception
     * rather than a null-dereference error.
     *
     * @param AgentContext $context The context to inspect.
     * @return Model The non-null primary record.
     * @throws InvalidContextException When $context->record is null.
     */
    protected function requireRecord(AgentContext $context): Model
    {
        if ($context->record === null) {
            throw InvalidContextException::missingRecord($context);
        }

        return $context->record;
    }

    /**
     * Assert that the context meta array contains the given key and return its value.
     *
     * @param string       $key     The required metadata key.
     * @param AgentContext $context The context to inspect.
     * @return mixed The metadata value for $key.
     * @throws InvalidContextException When $key is absent from $context->meta.
     */
    protected function requireMeta(string $key, AgentContext $context): mixed
    {
        if (! array_key_exists($key, $context->meta)) {
            throw InvalidContextException::missingMeta($context, $key);
        }

        return $context->meta[$key];
    }
}
