<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Pulse\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFactory;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;
use Pixelworxio\LaravelAiAction\Pulse\Recorders\AgentActionRecorder;

/**
 * Pulse card showing per-agent call volume, cost, latency, and token usage.
 *
 * Registered as the "pulse.ai-actions" Livewire component when
 * laravel/pulse is installed. Add it to your published dashboard view:
 *
 *   <livewire:pulse.ai-actions cols="6" />
 *
 * Extends Laravel\Pulse\Livewire\Card, so this file must never be
 * autoloaded when laravel/pulse is absent — the service provider only
 * references AgentActionsCard::class behind a class_exists() guard.
 */
#[Lazy]
final class AgentActionsCard extends Card
{
    public function render(): View
    {
        $calls = $this->aggregate('ai_action', ['sum', 'count'])->keyBy('key');
        $durations = $this->aggregate('ai_action_duration', ['avg', 'max'])->keyBy('key');
        $tokens = $this->aggregate('ai_action_tokens', ['sum'])->keyBy('key');

        $rows = $calls->keys()
            ->map(function (string $agentClass) use ($calls, $durations, $tokens): object {
                return (object) [
                    'agent' => class_basename($agentClass),
                    'calls' => (int) ($calls[$agentClass]->count ?? 0),
                    'cost' => (float) ($calls[$agentClass]->sum ?? 0) / AgentActionRecorder::COST_SCALE,
                    'avgDurationMs' => $durations[$agentClass]->avg ?? null,
                    'maxDurationMs' => $durations[$agentClass]->max ?? null,
                    'tokens' => $tokens[$agentClass]->sum ?? null,
                ];
            })
            ->sortByDesc('calls')
            ->values();

        return ViewFactory::make('ai-action::pulse.ai-actions', [
            'rows' => $rows,
        ]);
    }
}
