<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Contracts;

/**
 * Indicates that an agent action exposes tools to the AI provider.
 *
 * When RunAgentAction detects this interface it registers the returned
 * tool instances with the Laravel AI SDK before dispatching the prompt,
 * allowing the model to invoke them during generation.
 */
interface HasTools
{
    /**
     * Return the array of tool instances to make available to the AI model.
     *
     * Each element should be a Tool instance as accepted by the Laravel AI SDK
     * (i.e. implementing \Laravel\Ai\Contracts\Tool).
     *
     * @return array<int, \Laravel\Ai\Contracts\Tool> The tool instances.
     */
    public function tools(): array;
}
