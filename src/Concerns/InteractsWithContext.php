<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Concerns;

use Illuminate\Database\Eloquent\Model;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\Exceptions\InvalidContextException;

/**
 * Provides helper methods for inspecting and asserting AgentContext contents.
 *
 * Mix this trait into agent action classes that need to validate or extract
 * data from an AgentContext in a consistent, expressive manner without
 * duplicating the same guard clauses across multiple agents.
 */
trait InteractsWithContext
{
    /**
     * Assert that the context contains a non-null record and return it.
     *
     * @param AgentContext $context The context to inspect.
     * @return Model The non-null record.
     * @throws InvalidContextException When no record is present.
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
     * @param AgentContext $context The context to inspect.
     * @param string       $key     The required metadata key.
     * @return mixed The metadata value for the given key.
     * @throws InvalidContextException When the key is absent from meta.
     */
    protected function requireMeta(AgentContext $context, string $key): mixed
    {
        if (! array_key_exists($key, $context->meta)) {
            throw InvalidContextException::missingMeta($context, $key);
        }

        return $context->meta[$key];
    }

    /**
     * Retrieve a metadata value with an optional default.
     *
     * @param AgentContext $context The context to inspect.
     * @param string       $key     The metadata key to look up.
     * @param mixed        $default The default value when the key is absent.
     * @return mixed The metadata value or the default.
     */
    protected function meta(AgentContext $context, string $key, mixed $default = null): mixed
    {
        return $context->meta[$key] ?? $default;
    }

    /**
     * Determine whether the context carries a specific record type.
     *
     * @param AgentContext $context   The context to inspect.
     * @param class-string $modelClass The fully-qualified Eloquent model class.
     * @return bool True when the record is an instance of the given class.
     */
    protected function hasRecordOf(AgentContext $context, string $modelClass): bool
    {
        return $context->record instanceof $modelClass;
    }
}
