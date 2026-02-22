<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Contracts;

use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;

/**
 * Defines the contract for an AI agent action.
 *
 * Implement this interface on any class that encapsulates an AI agent
 * invocation. The handle() method is the primary entry point called by
 * RunAgentAction.
 */
interface AgentAction
{
    /**
     * Return the system-level instructions for the agent.
     *
     * @param AgentContext $context The runtime context for this invocation.
     * @return string The system prompt / instructions string.
     */
    public function instructions(AgentContext $context): string;

    /**
     * Return the user-facing prompt for the agent.
     *
     * @param AgentContext $context The runtime context for this invocation.
     * @return string The user prompt string.
     */
    public function prompt(AgentContext $context): string;

    /**
     * Return the AI provider identifier to use (e.g. "anthropic", "openai").
     *
     * @return string The provider key as configured in config/ai.php.
     */
    public function provider(): string;

    /**
     * Return the model identifier to use (e.g. "claude-sonnet-4-20250514").
     *
     * @return string The model name string.
     */
    public function model(): string;

    /**
     * Execute the agent action and return a structured result.
     *
     * @param AgentContext $context The runtime context for this invocation.
     * @return AgentResult The typed result wrapping the AI response.
     */
    public function handle(AgentContext $context): AgentResult;
}
