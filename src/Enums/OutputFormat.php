<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Enums;

/**
 * Describes the format of an AI agent action's output.
 *
 * - Text: Plain unformatted text output from the model.
 * - Structured: JSON schema-validated structured output (requires HasStructuredOutput).
 * - Markdown: Markdown-formatted text output from the model.
 */
enum OutputFormat
{
    /** The agent returned plain text. */
    case Text;

    /** The agent returned structured data conforming to a JSON schema. */
    case Structured;

    /** The agent returned Markdown-formatted text. */
    case Markdown;
}
