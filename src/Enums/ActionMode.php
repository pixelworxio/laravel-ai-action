<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Enums;

/**
 * Describes how an AI agent action should be executed.
 *
 * - Sync: Execute inline and block until a result is returned.
 * - Queued: Dispatch a RunAgentActionJob onto a queue for background processing.
 * - Streaming: Execute using a streaming response, processing chunks incrementally.
 */
enum ActionMode
{
    /** Execute the agent action synchronously, blocking until complete. */
    case Sync;

    /** Dispatch the agent action as a queued background job. */
    case Queued;

    /** Execute the agent action with a streaming response. */
    case Streaming;
}
