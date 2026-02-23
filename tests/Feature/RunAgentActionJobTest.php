<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Actions\RunAgentActionJob;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Testing\FakeAgentAction;

beforeEach(function (): void {
    FakeAgentAction::reset();
    Queue::fake();
});

// ---------------------------------------------------------------------------
// Fixture agent
// ---------------------------------------------------------------------------

function makeJobAgent(): AgentAction
{
    return new class implements AgentAction {
        public function instructions(AgentContext $context): string
        {
            return 'You are an assistant.';
        }

        public function prompt(AgentContext $context): string
        {
            return 'Summarize the context.';
        }

        public function provider(): string
        {
            return 'anthropic';
        }

        public function model(): string
        {
            return 'claude-sonnet-4-20250514';
        }

        public function handle(AgentContext $context): AgentResult
        {
            return app(RunAgentAction::class)->execute($this, $context);
        }
    };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('RunAgentActionJob', function (): void {
    it('implements ShouldQueue and ShouldBeUnique', function (): void {
        $reflection = new ReflectionClass(RunAgentActionJob::class);

        expect($reflection->implementsInterface(\Illuminate\Contracts\Queue\ShouldQueue::class))->toBeTrue()
            ->and($reflection->implementsInterface(\Illuminate\Contracts\Queue\ShouldBeUnique::class))->toBeTrue();
    });

    it('can be dispatched to the queue', function (): void {
        $agent = makeJobAgent();
        $context = AgentContext::fromRecords([], []);

        RunAgentActionJob::dispatch($agent, $context);

        Queue::assertPushed(RunAgentActionJob::class);
    });

    it('uses the configured queue name', function (): void {
        config(['ai-action.queue' => 'ai-work']);

        $agent = makeJobAgent();
        $context = AgentContext::fromRecords([], []);

        $job = new RunAgentActionJob($agent, $context);

        expect($job->queue)->toBe('ai-work');
    });

    it('generates a stable uniqueId for the same agent and context', function (): void {
        $agent = makeJobAgent();
        $context = AgentContext::fromRecords([], ['key' => 'value']);

        $job1 = new RunAgentActionJob($agent, $context);
        $job2 = new RunAgentActionJob($agent, $context);

        expect($job1->uniqueId())->toBe($job2->uniqueId());
    });

    it('generates different uniqueIds for different contexts', function (): void {
        $agent = makeJobAgent();
        $contextA = AgentContext::fromRecords([], ['key' => 'a']);
        $contextB = AgentContext::fromRecords([], ['key' => 'b']);

        $job1 = new RunAgentActionJob($agent, $contextA);
        $job2 = new RunAgentActionJob($agent, $contextB);

        expect($job1->uniqueId())->not->toBe($job2->uniqueId());
    });

    it('delegates to RunAgentAction::execute() when handled', function (): void {
        $agent = makeJobAgent();
        $context = AgentContext::fromRecords([], []);

        FakeAgentAction::fakeResponse($agent::class, 'Job result');

        $fake = new FakeAgentAction();
        $job = new RunAgentActionJob($agent, $context);
        $job->handle($fake);

        FakeAgentAction::assertAgentCalled($agent::class, 1);
    });

    it('stores agent and context as readonly constructor properties', function (): void {
        $agent = makeJobAgent();
        $context = AgentContext::fromRecords([], ['x' => 1]);

        $job = new RunAgentActionJob($agent, $context);

        expect($job->agent)->toBe($agent)
            ->and($job->context)->toBe($context);
    });
});
