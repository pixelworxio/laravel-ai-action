<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Testing;

use Illuminate\Support\Facades\App;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Test double that replaces RunAgentAction in the service container.
 *
 * Register faked responses with fakeResponse() before the system-under-test
 * runs, then assert invocation counts with assertAgentCalled() or
 * assertAgentNotCalled(). Call reset() between test cases to clear state.
 *
 * Usage in a Pest/PHPUnit test:
 *
 *   FakeAgentAction::fakeResponse(MyAgent::class, 'Hello world');
 *   // … run code that dispatches the agent …
 *   FakeAgentAction::assertAgentCalled(MyAgent::class);
 */
final class FakeAgentAction extends RunAgentAction
{
    /**
     * Registered fake responses keyed by agent class name.
     *
     * @var array<class-string, array{text: string, structured: mixed}>
     */
    private static array $fakeResponses = [];

    /**
     * Invocation log keyed by agent class name.
     *
     * @var array<class-string, list<AgentContext>>
     */
    private static array $calls = [];

    /**
     * Register a fake response for a specific agent class.
     *
     * Binds this fake as the RunAgentAction singleton in the container so that
     * no real API calls are ever made during tests.
     *
     * @param class-string $agentClass The fully-qualified agent class name.
     * @param string       $text       The fake text response.
     * @param mixed        $structured Optional structured value (for HasStructuredOutput agents).
     * @return void
     */
    public static function fakeResponse(string $agentClass, string $text, mixed $structured = null): void
    {
        static::$fakeResponses[$agentClass] = ['text' => $text, 'structured' => $structured];

        App::instance(RunAgentAction::class, new self());
    }

    /**
     * Assert that the given agent was invoked at least $times times.
     *
     * @param class-string $agentClass The fully-qualified agent class name.
     * @param int          $times      Expected minimum number of calls.
     * @return void
     */
    public static function assertAgentCalled(string $agentClass, int $times = 1): void
    {
        $actual = count(static::$calls[$agentClass] ?? []);

        PHPUnit::assertSame(
            $times,
            $actual,
            sprintf(
                'Expected agent [%s] to be called %d time(s), but it was called %d time(s).',
                $agentClass,
                $times,
                $actual,
            ),
        );
    }

    /**
     * Assert that the given agent was never invoked.
     *
     * @param class-string $agentClass The fully-qualified agent class name.
     * @return void
     */
    public static function assertAgentNotCalled(string $agentClass): void
    {
        $actual = count(static::$calls[$agentClass] ?? []);

        PHPUnit::assertSame(
            0,
            $actual,
            sprintf(
                'Expected agent [%s] not to be called, but it was called %d time(s).',
                $agentClass,
                $actual,
            ),
        );
    }

    /**
     * Clear all registered fake responses and call records.
     *
     * Call this in setUp() or tearDown() to ensure test isolation.
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$fakeResponses = [];
        static::$calls = [];
    }

    /**
     * Execute the fake: record the call and return the pre-registered response.
     *
     * If no fake has been registered for the agent class an AgentResult with
     * empty text is returned so tests never reach the real AI provider.
     *
     * @param AgentAction  $agent   The agent action being executed.
     * @param AgentContext $context The runtime context for the invocation.
     * @return AgentResult The pre-registered fake result.
     */
    public function execute(AgentAction $agent, AgentContext $context): AgentResult
    {
        $class = $agent::class;

        static::$calls[$class][] = $context;

        $registered = static::$fakeResponses[$class] ?? ['text' => '', 'structured' => null];

        $format = $registered['structured'] !== null
            ? OutputFormat::Structured
            : OutputFormat::Text;

        return new AgentResult(
            text: $registered['text'],
            format: $format,
            structured: $registered['structured'],
            inputTokens: 0,
            outputTokens: 0,
            provider: 'fake',
            model: 'fake',
            metadata: [],
        );
    }
}
