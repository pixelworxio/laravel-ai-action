<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Actions;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;

/**
 * Queued job wrapper for executing an AgentAction in the background.
 *
 * Dispatch this job instead of calling RunAgentAction::execute() directly
 * when you want the AI invocation to happen asynchronously. Implements
 * ShouldBeUnique to prevent duplicate jobs for the same agent/context pair
 * from accumulating in the queue.
 */
final class RunAgentActionJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /**
     * Create a new queued agent action job.
     *
     * @param AgentAction  $agent   The agent action to execute.
     * @param AgentContext $context The runtime context for the invocation.
     */
    public function __construct(
        public readonly AgentAction $agent,
        public readonly AgentContext $context,
    ) {
        $this->onQueue((string) config('ai-action.queue', 'default'));
    }

    /**
     * Execute the queued agent action via the RunAgentAction runner.
     *
     * The runner is injected from the service container, allowing it to be
     * swapped with a fake implementation during testing.
     *
     * @param RunAgentAction $runner The action runner resolved from the container.
     * @return void
     */
    public function handle(RunAgentAction $runner): void
    {
        $runner->execute($this->agent, $this->context);
    }

    /**
     * Return the unique identifier for this job to prevent queue duplication.
     *
     * The uniqueness key is derived from the agent class name combined with a
     * hash of the serialised context, ensuring that identical agent+context
     * pairs are deduplicated while distinct invocations remain independent.
     *
     * @return string The unique key for this job instance.
     */
    public function uniqueId(): string
    {
        return sprintf(
            '%s:%s',
            $this->agent::class,
            md5(serialize($this->context)),
        );
    }
}
