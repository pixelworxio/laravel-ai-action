<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Contracts;

use Pixelworxio\LaravelAiAction\DTOs\AgentResult;

/**
 * Indicates that an agent action should process the AI response as a stream.
 *
 * When RunAgentAction detects this interface it switches to streaming mode.
 * Each text chunk is passed to onChunk(); returning false halts the stream.
 * When the stream is exhausted onComplete() is called with the final result.
 */
interface HasStreamingResponse
{
    /**
     * Handle an individual text chunk arriving from the stream.
     *
     * Return true to continue consuming the stream, or false to halt early.
     *
     * @param string $chunk A single text delta chunk from the stream.
     * @return bool Whether to continue processing subsequent chunks.
     */
    public function onChunk(string $chunk): bool;

    /**
     * Handle the completion of the stream.
     *
     * Called once all chunks have been consumed (or after early termination
     * via onChunk returning false). The final AgentResult contains the full
     * concatenated text and usage metadata.
     *
     * @param AgentResult $result The completed agent result.
     * @return void
     */
    public function onComplete(AgentResult $result): void;
}
