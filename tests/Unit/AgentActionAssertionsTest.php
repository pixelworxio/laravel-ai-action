<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\AssertionFailedError;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;
use Pixelworxio\LaravelAiAction\Testing\AgentActionAssertions;
use Pixelworxio\LaravelAiAction\Testing\FakeAgentAction;

function makeAssertionAgent(): AgentAction
{
    return new class implements AgentAction
    {
        public function instructions(AgentContext $context): string
        {
            return '';
        }

        public function prompt(AgentContext $context): string
        {
            return '';
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

function makeTextResult(string $text = 'Hello'): AgentResult
{
    return new AgentResult($text, OutputFormat::Text, null, 10, 20, 'anthropic', 'claude-3', []);
}

function makeStructuredResult(mixed $structured): AgentResult
{
    return new AgentResult('{"key":"val"}', OutputFormat::Structured, $structured, 5, 10, 'anthropic', 'claude-3', []);
}

beforeEach(function (): void {
    FakeAgentAction::reset();
});

describe('AgentActionAssertions – static invocation helpers', function (): void {
    it('assertAgentWasCalled() passes when agent was invoked the expected number of times', function (): void {
        $agent = makeAssertionAgent();
        FakeAgentAction::fakeResponse($agent::class, 'ok');
        $fake = new FakeAgentAction;
        $fake->execute($agent, AgentContext::fromRecords([]));

        AgentActionAssertions::assertAgentWasCalled($agent::class, 1);
    });

    it('assertAgentWasNotCalled() passes when agent was never invoked', function (): void {
        AgentActionAssertions::assertAgentWasNotCalled('NonExistentAgentClass');
    });

    it('assertLastContextHadRecord() passes when last invocation had a record', function (): void {
        $record = Mockery::mock(Model::class);
        $agent = makeAssertionAgent();
        FakeAgentAction::fakeResponse($agent::class, 'ok');
        $fake = new FakeAgentAction;
        $fake->execute($agent, AgentContext::fromRecord($record));

        AgentActionAssertions::assertLastContextHadRecord($agent::class);
    });

    it('assertLastContextHadRecord() fails when record is null', function (): void {
        $agent = makeAssertionAgent();
        FakeAgentAction::fakeResponse($agent::class, 'ok');
        $fake = new FakeAgentAction;
        $fake->execute($agent, AgentContext::fromRecords([]));

        expect(fn () => AgentActionAssertions::assertLastContextHadRecord($agent::class))
            ->toThrow(AssertionFailedError::class);
    });

    it('assertLastContextHadMeta() passes when key and value match last context', function (): void {
        $agent = makeAssertionAgent();
        FakeAgentAction::fakeResponse($agent::class, 'ok');
        $fake = new FakeAgentAction;
        $fake->execute($agent, AgentContext::fromRecords([], ['locale' => 'en']));

        AgentActionAssertions::assertLastContextHadMeta($agent::class, 'locale', 'en');
    });

    it('assertLastContextHadMeta() fails when key is absent', function (): void {
        $agent = makeAssertionAgent();
        FakeAgentAction::fakeResponse($agent::class, 'ok');
        $fake = new FakeAgentAction;
        $fake->execute($agent, AgentContext::fromRecords([]));

        expect(fn () => AgentActionAssertions::assertLastContextHadMeta($agent::class, 'locale', 'en'))
            ->toThrow(AssertionFailedError::class);
    });

    it('assertLastContextHadMeta() fails when value does not match', function (): void {
        $agent = makeAssertionAgent();
        FakeAgentAction::fakeResponse($agent::class, 'ok');
        $fake = new FakeAgentAction;
        $fake->execute($agent, AgentContext::fromRecords([], ['locale' => 'fr']));

        expect(fn () => AgentActionAssertions::assertLastContextHadMeta($agent::class, 'locale', 'en'))
            ->toThrow(AssertionFailedError::class);
    });
});

describe('AgentActionAssertions – fluent result assertions', function (): void {
    it('assertTextContains() passes when substring is present', function (): void {
        $assertions = AgentActionAssertions::for(makeTextResult('Hello World'));

        $assertions->assertTextContains('World');
    });

    it('assertTextContains() fails when substring is absent', function (): void {
        $assertions = AgentActionAssertions::for(makeTextResult('Hello'));

        expect(fn () => $assertions->assertTextContains('World'))
            ->toThrow(AssertionFailedError::class);
    });

    it('assertTextContains() returns self for fluent chaining', function (): void {
        $assertions = AgentActionAssertions::for(makeTextResult('Hello World'));

        expect($assertions->assertTextContains('Hello'))->toBe($assertions);
    });

    it('getResult() returns the underlying AgentResult', function (): void {
        $result = makeTextResult('test');
        $assertions = AgentActionAssertions::for($result);

        expect($assertions->getResult())->toBe($result);
    });
});
