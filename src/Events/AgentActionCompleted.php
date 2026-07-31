<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Events;

use Pixelworxio\LaravelAiAction\DTOs\AgentResult;

/**
 * Dispatched after every successful RunAgentAction::execute() call.
 *
 * Carries enough data for observability integrations — such as the bundled
 * Laravel Pulse recorder — to record cost, token usage, and latency without
 * coupling them to RunAgentAction itself. Not dispatched when the action
 * fails; listen for AgentException instead to observe failures.
 */
final readonly class AgentActionCompleted
{
    /**
     * @param  class-string  $agentClass  The fully-qualified agent action class that ran.
     * @param  AgentResult  $result  The result the action produced.
     * @param  float  $durationMs  Wall-clock time spent inside RunAgentAction::execute(), in milliseconds.
     */
    public function __construct(
        public string $agentClass,
        public AgentResult $result,
        public float $durationMs,
    ) {}
}
