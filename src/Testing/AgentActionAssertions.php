<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Testing;

use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use PHPUnit\Framework\Assert as PHPUnit;
use ReflectionClass;

/**
 * Static assertion helpers for agent invocation state and AgentResult objects.
 *
 * Pairs with FakeAgentAction to let Pest and PHPUnit tests make expressive
 * assertions about which agents were called, how many times, and with what
 * context — without ever reaching a real AI provider.
 *
 * Invocation-state assertions (assertAgentWasCalled, assertLastContextHadRecord,
 * etc.) read directly from FakeAgentAction's internal call log. Result-shape
 * assertions are available via the fluent factory method for():
 *
 *   AgentActionAssertions::for($result)->assertText('Hello')->assertIsText();
 */
final class AgentActionAssertions
{
    /**
     * The AgentResult instance under assertion.
     *
     * @var AgentResult
     */
    private readonly AgentResult $result;

    /**
     * Create a new assertions instance wrapping the given result.
     *
     * @param AgentResult $result The result to assert against.
     */
    public function __construct(AgentResult $result)
    {
        $this->result = $result;
    }

    // -------------------------------------------------------------------------
    // Static invocation-state assertions
    // -------------------------------------------------------------------------

    /**
     * Assert that the given agent was invoked exactly $times times.
     *
     * Delegates to FakeAgentAction::assertAgentCalled() so failure messages
     * remain consistent with the test double's own assertions.
     *
     * @param class-string $agentClass The fully-qualified agent class name.
     * @param int          $times      The expected exact invocation count.
     * @return void
     */
    public static function assertAgentWasCalled(string $agentClass, int $times = 1): void
    {
        FakeAgentAction::assertAgentCalled($agentClass, $times);
    }

    /**
     * Assert that the given agent was never invoked.
     *
     * Delegates to FakeAgentAction::assertAgentNotCalled().
     *
     * @param class-string $agentClass The fully-qualified agent class name.
     * @return void
     */
    public static function assertAgentWasNotCalled(string $agentClass): void
    {
        FakeAgentAction::assertAgentNotCalled($agentClass);
    }

    /**
     * Assert that the last invocation of the given agent carried a non-null record.
     *
     * Reads the call log from FakeAgentAction's private static $calls property
     * via ReflectionClass so that no changes to FakeAgentAction are required.
     *
     * @param class-string $agentClass The fully-qualified agent class name.
     * @return void
     */
    public static function assertLastContextHadRecord(string $agentClass): void
    {
        $context = self::lastContext($agentClass);

        PHPUnit::assertNotNull(
            $context->record,
            sprintf(
                'Expected the last invocation of [%s] to have a record, but $context->record was null.',
                $agentClass,
            ),
        );
    }

    /**
     * Assert that a specific metadata key/value pair was present in the last context.
     *
     * @param class-string $agentClass The fully-qualified agent class name.
     * @param string       $key        The metadata key to check.
     * @param mixed        $expected   The expected value for that key.
     * @return void
     */
    public static function assertLastContextHadMeta(string $agentClass, string $key, mixed $expected): void
    {
        $context = self::lastContext($agentClass);

        PHPUnit::assertArrayHasKey(
            $key,
            $context->meta,
            sprintf(
                'Expected the last invocation of [%s] to have meta key "%s", but it was absent.',
                $agentClass,
                $key,
            ),
        );

        PHPUnit::assertEquals(
            $expected,
            $context->meta[$key],
            sprintf(
                'Expected meta["%s"] of the last [%s] invocation to equal the given value.',
                $key,
                $agentClass,
            ),
        );
    }

    // -------------------------------------------------------------------------
    // Fluent factory
    // -------------------------------------------------------------------------

    /**
     * Create a fluent assertions instance wrapping the given AgentResult.
     *
     * @param AgentResult $result The result to assert against.
     * @return self
     */
    public static function for(AgentResult $result): self
    {
        return new self($result);
    }

    // -------------------------------------------------------------------------
    // Fluent result assertions
    // -------------------------------------------------------------------------

    /**
     * Assert that the result text equals the expected value.
     *
     * @param string $expected The expected text.
     * @return self For fluent chaining.
     */
    public function assertText(string $expected): self
    {
        PHPUnit::assertSame(
            $expected,
            $this->result->text,
            'Agent result text does not match expected value.',
        );

        return $this;
    }

    /**
     * Assert that the result text contains the given substring.
     *
     * @param string $needle The substring to search for.
     * @return self For fluent chaining.
     */
    public function assertTextContains(string $needle): self
    {
        PHPUnit::assertStringContainsString(
            $needle,
            $this->result->text,
            'Agent result text does not contain the expected substring.',
        );

        return $this;
    }

    /**
     * Assert that the result carries structured output.
     *
     * @return self For fluent chaining.
     */
    public function assertIsStructured(): self
    {
        PHPUnit::assertTrue(
            $this->result->isStructured(),
            'Expected the agent result to be structured, but it is not.',
        );

        return $this;
    }

    /**
     * Assert that the result is plain (non-structured) text.
     *
     * @return self For fluent chaining.
     */
    public function assertIsText(): self
    {
        PHPUnit::assertFalse(
            $this->result->isStructured(),
            'Expected the agent result to be plain text, but it is structured.',
        );

        return $this;
    }

    /**
     * Assert that the structured output equals the expected value.
     *
     * @param mixed $expected The expected structured value.
     * @return self For fluent chaining.
     */
    public function assertStructured(mixed $expected): self
    {
        PHPUnit::assertEquals(
            $expected,
            $this->result->structured,
            'Agent result structured output does not match expected value.',
        );

        return $this;
    }

    /**
     * Assert that the provider matches the expected string.
     *
     * @param string $expected The expected provider key.
     * @return self For fluent chaining.
     */
    public function assertProvider(string $expected): self
    {
        PHPUnit::assertSame(
            $expected,
            $this->result->provider,
            'Agent result provider does not match expected value.',
        );

        return $this;
    }

    /**
     * Assert that the model matches the expected string.
     *
     * @param string $expected The expected model identifier.
     * @return self For fluent chaining.
     */
    public function assertModel(string $expected): self
    {
        PHPUnit::assertSame(
            $expected,
            $this->result->model,
            'Agent result model does not match expected value.',
        );

        return $this;
    }

    /**
     * Assert that the input token count equals the expected value.
     *
     * @param int $expected The expected input token count.
     * @return self For fluent chaining.
     */
    public function assertInputTokens(int $expected): self
    {
        PHPUnit::assertSame(
            $expected,
            $this->result->inputTokens,
            'Agent result input token count does not match expected value.',
        );

        return $this;
    }

    /**
     * Assert that the output token count equals the expected value.
     *
     * @param int $expected The expected output token count.
     * @return self For fluent chaining.
     */
    public function assertOutputTokens(int $expected): self
    {
        PHPUnit::assertSame(
            $expected,
            $this->result->outputTokens,
            'Agent result output token count does not match expected value.',
        );

        return $this;
    }

    /**
     * Return the underlying AgentResult for direct property access.
     *
     * @return AgentResult The wrapped result instance.
     */
    public function getResult(): AgentResult
    {
        return $this->result;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Retrieve the AgentContext from the last recorded invocation of an agent.
     *
     * Reads FakeAgentAction's private static $calls property via ReflectionClass
     * so that FakeAgentAction's encapsulation is preserved without modification.
     *
     * @param class-string $agentClass The fully-qualified agent class name.
     * @return AgentContext The context from the most recent invocation.
     */
    private static function lastContext(string $agentClass): AgentContext
    {
        $reflection = new ReflectionClass(FakeAgentAction::class);
        $property   = $reflection->getProperty('calls');

        /** @var array<class-string, list<AgentContext>> $calls */
        $calls = $property->getValue(null);

        PHPUnit::assertArrayHasKey(
            $agentClass,
            $calls,
            sprintf('Agent [%s] was never invoked.', $agentClass),
        );

        PHPUnit::assertNotEmpty(
            $calls[$agentClass],
            sprintf('Agent [%s] has no recorded invocations.', $agentClass),
        );

        $lastContext = end($calls[$agentClass]);
        PHPUnit::assertInstanceOf(AgentContext::class, $lastContext);

        return $lastContext;
    }
}
