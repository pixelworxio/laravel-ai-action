<?php

declare(strict_types=1);

use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Contracts\HasStreamingResponse;
use Pixelworxio\LaravelAiAction\Contracts\HasStructuredOutput;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;
use Pixelworxio\LaravelAiAction\Exceptions\AgentException;
use Pixelworxio\LaravelAiAction\Testing\AgentActionAssertions;
use Pixelworxio\LaravelAiAction\Testing\FakeAgentAction;

beforeEach(function (): void {
    FakeAgentAction::reset();
});

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

function makeContext(): AgentContext
{
    return AgentContext::fromRecords([], ['topic' => 'testing']);
}

/**
 * @return AgentAction An anonymous agent action that returns the fake result via FakeAgentAction.
 */
function makeTextAgent(string $agentClass = 'TestTextAgent'): AgentAction
{
    return new class ($agentClass) implements AgentAction {
        public function __construct(private readonly string $agentClass) {}

        public function instructions(AgentContext $context): string
        {
            return 'You are a helpful assistant.';
        }

        public function prompt(AgentContext $context): string
        {
            return 'Say hello.';
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

        public function getClass(): string
        {
            return $this->agentClass;
        }
    };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('RunAgentAction with FakeAgentAction', function (): void {
    it('is bound as a singleton in the container', function (): void {
        $a = app(RunAgentAction::class);
        $b = app(RunAgentAction::class);

        expect($a)->toBe($b);
    });

    it('FakeAgentAction replaces the singleton after fakeResponse()', function (): void {
        FakeAgentAction::fakeResponse(AgentAction::class, 'Fake text');

        $runner = app(RunAgentAction::class);

        expect($runner)->toBeInstanceOf(FakeAgentAction::class);
    });

    it('returns a pre-registered fake text result', function (): void {
        $agent = new class implements AgentAction {
            public function instructions(AgentContext $context): string { return ''; }
            public function prompt(AgentContext $context): string { return ''; }
            public function provider(): string { return 'anthropic'; }
            public function model(): string { return 'claude-sonnet-4-20250514'; }
            public function handle(AgentContext $context): AgentResult
            {
                return app(RunAgentAction::class)->execute($this, $context);
            }
        };

        FakeAgentAction::fakeResponse($agent::class, 'Hello from fake');

        $result = (new FakeAgentAction())->execute($agent, makeContext());

        AgentActionAssertions::for($result)
            ->assertText('Hello from fake')
            ->assertIsText()
            ->assertProvider('fake')
            ->assertModel('fake')
            ->assertInputTokens(0)
            ->assertOutputTokens(0);
    });

    it('records the call and allows assertAgentCalled()', function (): void {
        $agent = new class implements AgentAction {
            public function instructions(AgentContext $context): string { return ''; }
            public function prompt(AgentContext $context): string { return ''; }
            public function provider(): string { return 'anthropic'; }
            public function model(): string { return 'claude-sonnet-4-20250514'; }
            public function handle(AgentContext $context): AgentResult
            {
                return app(RunAgentAction::class)->execute($this, $context);
            }
        };

        FakeAgentAction::fakeResponse($agent::class, 'response');

        $fake = new FakeAgentAction();
        $fake->execute($agent, makeContext());
        $fake->execute($agent, makeContext());

        FakeAgentAction::assertAgentCalled($agent::class, 2);
    });

    it('assertAgentNotCalled() passes when the agent was never invoked', function (): void {
        FakeAgentAction::assertAgentNotCalled('NonExistentAgent');
    });

    it('returns a structured result when structured payload is registered', function (): void {
        $agent = new class implements AgentAction, HasStructuredOutput {
            public function instructions(AgentContext $context): string { return ''; }
            public function prompt(AgentContext $context): string { return ''; }
            public function provider(): string { return 'anthropic'; }
            public function model(): string { return 'claude-sonnet-4-20250514'; }
            public function outputSchema(): array { return ['type' => 'object']; }
            public function mapOutput(array $raw): mixed { return $raw; }
            public function handle(AgentContext $context): AgentResult
            {
                return app(RunAgentAction::class)->execute($this, $context);
            }
        };

        $structured = ['name' => 'Test', 'value' => 42];
        FakeAgentAction::fakeResponse($agent::class, '{"name":"Test","value":42}', $structured);

        $fake = new FakeAgentAction();
        $result = $fake->execute($agent, makeContext());

        AgentActionAssertions::for($result)
            ->assertIsStructured()
            ->assertStructured($structured);
    });

    it('reset() clears all registered responses and call records', function (): void {
        $agent = new class implements AgentAction {
            public function instructions(AgentContext $context): string { return ''; }
            public function prompt(AgentContext $context): string { return ''; }
            public function provider(): string { return 'anthropic'; }
            public function model(): string { return 'claude-sonnet-4-20250514'; }
            public function handle(AgentContext $context): AgentResult
            {
                return app(RunAgentAction::class)->execute($this, $context);
            }
        };

        FakeAgentAction::fakeResponse($agent::class, 'text');
        $fake = new FakeAgentAction();
        $fake->execute($agent, makeContext());

        FakeAgentAction::reset();

        FakeAgentAction::assertAgentNotCalled($agent::class);
    });

    it('returns an empty-text result when no fake is registered for the agent', function (): void {
        $agent = new class implements AgentAction {
            public function instructions(AgentContext $context): string { return ''; }
            public function prompt(AgentContext $context): string { return ''; }
            public function provider(): string { return 'anthropic'; }
            public function model(): string { return 'claude-sonnet-4-20250514'; }
            public function handle(AgentContext $context): AgentResult
            {
                return app(RunAgentAction::class)->execute($this, $context);
            }
        };

        $fake = new FakeAgentAction();
        $result = $fake->execute($agent, makeContext());

        expect($result->text)->toBe('');
    });
});
