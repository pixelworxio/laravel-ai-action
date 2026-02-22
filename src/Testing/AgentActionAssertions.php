<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Testing;

use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Fluent assertion helpers for AgentResult objects.
 *
 * Use these methods in feature or unit tests to make expressive assertions
 * against the AgentResult returned by RunAgentAction::execute() or surfaced
 * via FakeAgentAction.
 *
 * @mixin AgentResult
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

    /**
     * Create an assertions instance wrapping the given AgentResult.
     *
     * @param AgentResult $result The result to assert against.
     * @return self
     */
    public static function for(AgentResult $result): self
    {
        return new self($result);
    }

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
}
