<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Support;

/**
 * Resolves per-token pricing from config('ai-action.pricing') and computes
 * the USD cost of a single agent invocation.
 *
 * Pricing is looked up by provider and exact model identifier. When no rate
 * is configured for a given provider/model pair, costFor() returns null
 * rather than 0.0 — silently reporting "free" would be misleading, and an
 * explicit null lets callers distinguish "no data" from "no cost."
 */
final class Pricing
{
    /**
     * Compute the USD cost of a call, or null when no rate is configured.
     *
     * @param  string  $provider  The provider key (e.g. "anthropic", "openai").
     * @param  string  $model  The model identifier used for the call.
     * @param  int  $inputTokens  Number of input (prompt) tokens consumed.
     * @param  int  $outputTokens  Number of output (completion) tokens generated.
     * @return float|null The computed cost in USD, or null when unpriced.
     */
    public static function costFor(string $provider, string $model, int $inputTokens, int $outputTokens): ?float
    {
        $rates = config("ai-action.pricing.{$provider}.{$model}");

        if (! is_array($rates) || ! isset($rates['input'], $rates['output'])) {
            return null;
        }

        return ($inputTokens / 1_000_000 * (float) $rates['input'])
            + ($outputTokens / 1_000_000 * (float) $rates['output']);
    }
}
