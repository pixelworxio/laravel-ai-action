<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Contracts;

/**
 * Indicates that an agent action returns structured (JSON schema) output.
 *
 * When RunAgentAction detects this interface it activates structured mode,
 * passes the schema to the AI provider, and routes the raw output array
 * through mapOutput() before constructing the AgentResult.
 */
interface HasStructuredOutput
{
    /**
     * Return the JSON schema describing the expected output structure.
     *
     * The schema is expressed as a plain PHP array following the JSON Schema
     * specification (type, properties, required, etc.).
     *
     * @return array<string, mixed> The JSON schema array.
     */
    public function outputSchema(): array;

    /**
     * Map the raw structured output array to the desired return value.
     *
     * This method receives the raw associative array decoded from the AI
     * provider's structured response and should return whatever shape your
     * application expects (a DTO, a collection, a primitive, etc.).
     *
     * @param array<string, mixed> $raw The decoded structured output.
     * @return mixed The mapped output value.
     */
    public function mapOutput(array $raw): mixed;
}
