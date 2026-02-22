<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\DTOs;

use Pixelworxio\LaravelAiAction\Enums\OutputFormat;

/**
 * Immutable value object representing the result of an AI agent action.
 *
 * AgentResult wraps the AI provider's response alongside usage statistics
 * and metadata. When the action implements HasStructuredOutput the $structured
 * property holds the mapped domain value returned by mapOutput().
 */
final readonly class AgentResult
{
    /**
     * @param string                $text         The raw text returned by the model.
     * @param OutputFormat          $format       The format of the output (Text, Structured, Markdown).
     * @param mixed                 $structured   The mapped structured value, or null for non-structured output.
     * @param int                   $inputTokens  The number of input (prompt) tokens consumed.
     * @param int                   $outputTokens The number of output (completion) tokens generated.
     * @param string                $provider     The provider key used for this invocation.
     * @param string                $model        The model identifier used for this invocation.
     * @param array<string, mixed>  $metadata     Additional provider-specific metadata.
     */
    public function __construct(
        public readonly string $text,
        public readonly OutputFormat $format,
        public readonly mixed $structured,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly string $provider,
        public readonly string $model,
        public readonly array $metadata,
    ) {}

    /**
     * Determine whether this result contains structured output.
     *
     * @return bool True when $format is OutputFormat::Structured.
     */
    public function isStructured(): bool
    {
        return $this->format === OutputFormat::Structured;
    }

    /**
     * Serialize the result to a plain array representation.
     *
     * @return array<string, mixed> The result as an associative array.
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'format' => $this->format->name,
            'structured' => $this->structured,
            'input_tokens' => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'provider' => $this->provider,
            'model' => $this->model,
            'metadata' => $this->metadata,
        ];
    }
}
