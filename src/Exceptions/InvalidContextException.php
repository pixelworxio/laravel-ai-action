<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Exceptions;

use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use InvalidArgumentException;

/**
 * Thrown when an AgentContext is missing required data for a given agent.
 *
 * Agents that require a specific record, record type, or metadata key should
 * throw this exception when the provided AgentContext does not satisfy those
 * prerequisites, allowing callers to surface a clear, actionable error.
 */
final class InvalidContextException extends InvalidArgumentException
{
    /**
     * The context that failed validation.
     *
     * @var AgentContext
     */
    private readonly AgentContext $context;

    /**
     * Create a new InvalidContextException.
     *
     * @param AgentContext     $context  The invalid context object.
     * @param string           $message  A description of why the context is invalid.
     * @param int              $code     The exception code.
     * @param \Throwable|null  $previous The previous exception for chaining.
     */
    public function __construct(
        AgentContext $context,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        $this->context = $context;

        parent::__construct(
            message: $message ?: 'The provided AgentContext is invalid or missing required data.',
            code: $code,
            previous: $previous,
        );
    }

    /**
     * Return the AgentContext that triggered this exception.
     *
     * @return AgentContext The invalid context.
     */
    public function getContext(): AgentContext
    {
        return $this->context;
    }

    /**
     * Create an exception indicating a required record is missing from the context.
     *
     * @param AgentContext $context The context lacking a record.
     * @return self A new InvalidContextException.
     */
    public static function missingRecord(AgentContext $context): self
    {
        return new self(
            context: $context,
            message: 'The AgentContext must contain a record, but none was provided.',
        );
    }

    /**
     * Create an exception indicating a required metadata key is absent.
     *
     * @param AgentContext $context The context missing the metadata key.
     * @param string       $key     The missing metadata key name.
     * @return self A new InvalidContextException.
     */
    public static function missingMeta(AgentContext $context, string $key): self
    {
        return new self(
            context: $context,
            message: sprintf('The AgentContext is missing required metadata key "%s".', $key),
        );
    }
}
