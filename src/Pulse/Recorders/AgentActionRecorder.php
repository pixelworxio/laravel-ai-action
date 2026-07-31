<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Pulse\Recorders;

use Illuminate\Support\Facades\Config;
use Laravel\Pulse\Facades\Pulse;
use Pixelworxio\LaravelAiAction\Events\AgentActionCompleted;

/**
 * Records every completed agent action to Laravel Pulse.
 *
 * Follows Pulse's package-recorder convention exactly: a public $listen
 * property naming the event to subscribe to, and a record() method that
 * feeds Pulse::record(). Users opt in by adding this class to the
 * 'recorders' array in their own config/pulse.php — see docs/pulse.md.
 *
 * This file only references Laravel\Pulse\Facades\Pulse inside record(),
 * so it autoloads safely even when laravel/pulse is not installed; it is
 * simply never invoked because nothing subscribes it without that config
 * entry.
 *
 * Pulse::record() only accepts integer values, so cost — normally a
 * fraction of a cent per call — is stored as micro-USD (cost * 1,000,000)
 * to avoid truncating it to zero. AgentActionsCard divides back down by
 * the same factor before display.
 */
final class AgentActionRecorder
{
    /**
     * Cost values are stored as micro-USD (1 USD = 1,000,000) so that
     * Pulse's integer-only storage doesn't truncate sub-cent costs to zero.
     */
    public const COST_SCALE = 1_000_000;

    /**
     * The events to listen for.
     *
     * @var array<int, class-string>
     */
    public array $listen = [
        AgentActionCompleted::class,
    ];

    /**
     * Record the completed agent action.
     */
    public function record(AgentActionCompleted $event): void
    {
        $config = Config::get('pulse.recorders.'.self::class, []);

        if (($config['enabled'] ?? true) === false) {
            return;
        }

        $costMicros = (int) round(($event->result->cost() ?? 0.0) * self::COST_SCALE);
        $durationMs = (int) round($event->durationMs);
        $tokens = $event->result->inputTokens + $event->result->outputTokens;

        Pulse::record('ai_action', $event->agentClass, $costMicros)
            ->sum()
            ->count();

        Pulse::record('ai_action_duration', $event->agentClass, $durationMs)
            ->avg()
            ->max();

        Pulse::record('ai_action_tokens', $event->agentClass, $tokens)
            ->sum();
    }
}
