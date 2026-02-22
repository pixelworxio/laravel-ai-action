<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Concerns;

/**
 * Provides default provider() and model() implementations for agent actions.
 *
 * Classes using this trait satisfy the provider() and model() requirements
 * of the AgentAction contract by delegating to config('ai-action.provider')
 * and config('ai-action.model'). Only instructions(), prompt(), and handle()
 * remain for the implementing class to define.
 */
trait InteractsWithAgent
{
    /**
     * Return the AI provider key as configured in config/ai-action.php.
     *
     * @return string The provider key (e.g. "anthropic", "openai").
     */
    public function provider(): string
    {
        return (string) config('ai-action.provider', 'anthropic');
    }

    /**
     * Return the AI model identifier as configured in config/ai-action.php.
     *
     * @return string The model name (e.g. "claude-sonnet-4-20250514").
     */
    public function model(): string
    {
        return (string) config('ai-action.model', 'claude-sonnet-4-20250514');
    }
}
