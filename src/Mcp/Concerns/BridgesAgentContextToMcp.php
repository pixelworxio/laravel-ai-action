<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Mcp\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\Exceptions\InvalidContextException;

/**
 * Convenience helpers for implementing ExposedAsMcpTool::resolveContext().
 *
 * Mix this trait into any action class that needs to translate flat MCP input
 * arrays into Eloquent records and AgentContext metadata. All resolution runs
 * through standard Eloquent, so global scopes, tenant scopes, and soft-delete
 * guards apply automatically.
 *
 * The $user parameter is passed through to every helper so that implementing
 * actions can add auth-scoped query constraints when needed — the trait itself
 * does not enforce any auth policy.
 *
 * @example
 * ```php
 * public function resolveContext(array $input, ?Authenticatable $user): AgentContext
 * {
 *     $invoice = $this->resolveRecord(Invoice::class, $input['invoice_id'], $user);
 *     return AgentContext::fromRecord($invoice, $this->metaFromInput($input, ['locale']));
 * }
 * ```
 */
trait BridgesAgentContextToMcp
{
    /**
     * Resolve a single Eloquent record by primary key.
     *
     * Runs findOrFail through the model class so global scopes (tenant, soft
     * delete, etc.) apply. Pass $user if your model's global scopes or the
     * calling code needs the authenticated identity to narrow the query.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass  The fully-qualified Eloquent model class.
     * @param  int|string  $id  The primary key value to look up.
     * @param  Authenticatable|null  $user  The authenticated user (unused by the trait; available for callers).
     * @return TModel The resolved model instance.
     *
     * @throws InvalidContextException When the record does not exist.
     */
    protected function resolveRecord(string $modelClass, int|string $id, ?Authenticatable $user): Model
    {
        try {
            /** @var TModel $record */
            $record = $modelClass::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new InvalidContextException(
                context: new AgentContext(
                    record: null,
                    records: [],
                    meta: [],
                    userInstruction: null,
                    panelId: null,
                    resourceClass: null,
                ),
                message: sprintf(
                    'Could not resolve %s with id [%s].',
                    class_basename($modelClass),
                    $id,
                ),
                previous: $e,
            );
        }

        return $record;
    }

    /**
     * Resolve a collection of Eloquent records by primary key.
     *
     * Returns models in the order Eloquent returns them (typically ascending
     * primary key). Missing IDs are silently omitted; use findOrFail-based
     * logic in resolveContext() if strict presence is required.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass  The fully-qualified Eloquent model class.
     * @param  array<int|string>  $ids  The primary key values to look up.
     * @param  Authenticatable|null  $user  The authenticated user (passed for caller use).
     * @return array<int, TModel> The resolved model instances.
     */
    protected function resolveRecords(string $modelClass, array $ids, ?Authenticatable $user): array
    {
        /** @var Collection<int, TModel> $collection */
        $collection = $modelClass::findMany($ids);

        return $collection->all();
    }

    /**
     * Extract a subset of keys from the MCP input array as a metadata map.
     *
     * Only keys present in both $keys and $input are returned. Missing keys are
     * silently omitted, so the caller may check for them via requireMeta() or
     * supply defaults in AgentContext::withMeta().
     *
     * @param  array<string, mixed>  $input  The full MCP input payload.
     * @param  list<string>  $keys  The keys to extract.
     * @return array<string, mixed> A subset of $input containing only $keys.
     */
    protected function metaFromInput(array $input, array $keys): array
    {
        return array_intersect_key($input, array_flip($keys));
    }
}
