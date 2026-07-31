<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\DTOs;

use Illuminate\Database\Eloquent\Model;

/**
 * Immutable context object passed into every AI agent action invocation.
 *
 * AgentContext carries the Eloquent record(s) and arbitrary metadata needed
 * to build prompts. It is constructed via static factory methods and mutated
 * only through the immutable withMeta() method which returns a new instance.
 */
final readonly class AgentContext
{
    /**
     * @param  Model|null  $record  The primary Eloquent record for this invocation.
     * @param  array<int, Model>  $records  A collection of Eloquent records (batch operations).
     * @param  array<string, mixed>  $meta  Arbitrary key/value metadata for prompt building.
     * @param  string|null  $userInstruction  Optional free-text instruction from the end user.
     * @param  string|null  $panelId  Optional Filament panel identifier.
     * @param  string|null  $resourceClass  Optional Filament resource class name.
     * @param  string|null  $providerOverride  Provider key to use instead of the agent's own provider(), set by middleware such as FallbackProvider.
     * @param  string|null  $modelOverride  Model identifier to use instead of the agent's own model(), set alongside $providerOverride.
     */
    public function __construct(
        public readonly ?Model $record,
        public readonly array $records,
        public readonly array $meta,
        public readonly ?string $userInstruction,
        public readonly ?string $panelId,
        public readonly ?string $resourceClass,
        public readonly ?string $providerOverride = null,
        public readonly ?string $modelOverride = null,
    ) {}

    /**
     * Create a context for a single Eloquent record.
     *
     * @param  Model  $record  The primary record.
     * @param  array<string, mixed>  $meta  Optional initial metadata.
     * @return self A new AgentContext instance.
     */
    public static function fromRecord(Model $record, array $meta = []): self
    {
        return new self(
            record: $record,
            records: [],
            meta: $meta,
            userInstruction: null,
            panelId: null,
            resourceClass: null,
        );
    }

    /**
     * Create a context for a batch of Eloquent records.
     *
     * @param  array<int, Model>  $records  The batch of records.
     * @param  array<string, mixed>  $meta  Optional initial metadata.
     * @return self A new AgentContext instance.
     */
    public static function fromRecords(array $records, array $meta = []): self
    {
        return new self(
            record: null,
            records: $records,
            meta: $meta,
            userInstruction: null,
            panelId: null,
            resourceClass: null,
        );
    }

    /**
     * Return a new instance with an additional metadata key/value pair.
     *
     * The original instance is not modified (immutability is preserved).
     *
     * @param  string  $key  The metadata key.
     * @param  mixed  $value  The metadata value.
     * @return self A new AgentContext instance with the additional metadata.
     */
    public function withMeta(string $key, mixed $value): self
    {
        return new self(
            record: $this->record,
            records: $this->records,
            meta: array_merge($this->meta, [$key => $value]),
            userInstruction: $this->userInstruction,
            panelId: $this->panelId,
            resourceClass: $this->resourceClass,
            providerOverride: $this->providerOverride,
            modelOverride: $this->modelOverride,
        );
    }

    /**
     * Return a new instance that overrides the provider (and optionally model)
     * used for this invocation.
     *
     * Used by middleware such as FallbackProvider to redirect execution to a
     * different provider without the agent itself needing to know.
     *
     * @param  string  $provider  The provider key to use instead of the agent's provider().
     * @param  string|null  $model  Optional model identifier to use instead of the agent's model().
     * @return self A new AgentContext instance carrying the override.
     */
    public function withProvider(string $provider, ?string $model = null): self
    {
        return new self(
            record: $this->record,
            records: $this->records,
            meta: $this->meta,
            userInstruction: $this->userInstruction,
            panelId: $this->panelId,
            resourceClass: $this->resourceClass,
            providerOverride: $provider,
            modelOverride: $model ?? $this->modelOverride,
        );
    }
}
