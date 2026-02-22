<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Exceptions;

use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use RuntimeException;

/**
 * Thrown when an AI agent action fails during execution.
 *
 * This exception wraps provider-level errors, timeout failures, or any
 * unexpected exception raised while invoking the Laravel AI SDK. The
 * originating AgentAction class name is recorded to aid debugging.
 */
final class AgentException extends RuntimeException
{
    /**
     * The fully-qualified class name of the agent that raised the exception.
     *
     * @var string
     */
    private readonly string $agentClass;

    /**
     * Create a new AgentException for the given agent action.
     *
     * @param AgentAction       $agent   The agent action that failed.
     * @param string            $message A human-readable description of the failure.
     * @param int               $code    The exception code.
     * @param \Throwable|null   $previous The previous exception for chaining.
     */
    public function __construct(
        AgentAction $agent,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        $this->agentClass = $agent::class;

        parent::__construct(
            message: $message ?: sprintf('Agent [%s] failed during execution.', $this->agentClass),
            code: $code,
            previous: $previous,
        );
    }

    /**
     * Return the fully-qualified class name of the agent that raised this exception.
     *
     * @return string The agent class name.
     */
    public function getAgentClass(): string
    {
        return $this->agentClass;
    }

    /**
     * Create an AgentException from an existing throwable, wrapping it as the cause.
     *
     * @param AgentAction $agent    The agent action that failed.
     * @param \Throwable  $cause    The underlying exception.
     * @return self A new AgentException instance wrapping the cause.
     */
    public static function fromThrowable(AgentAction $agent, \Throwable $cause): self
    {
        return new self(
            agent: $agent,
            message: sprintf(
                'Agent [%s] failed: %s',
                $agent::class,
                $cause->getMessage(),
            ),
            code: (int) $cause->getCode(),
            previous: $cause,
        );
    }
}
