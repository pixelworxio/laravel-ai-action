# Laravel Pulse Integration (opt-in)

See what your `AgentAction`s are actually doing in production — call volume, cost, latency, and token usage — inside [Laravel Pulse](https://laravel.com/docs/pulse), a dashboard you likely already have running rather than a bespoke one this package would have to maintain.

## Setup

### 1 — Install Laravel Pulse

```bash
composer require laravel/pulse
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
php artisan migrate
```

Pulse's first-party storage requires MySQL, MariaDB, or PostgreSQL — see the [Pulse installation docs](https://laravel.com/docs/pulse#installation) if that's not your primary database.

### 2 — Enable the integration

```env
AI_ACTION_PULSE_ENABLED=true
```

### 3 — Register the recorder

Add `AgentActionRecorder` to the `recorders` array in your published `config/pulse.php`, exactly as you would any other custom recorder:

```php
use Pixelworxio\LaravelAiAction\Pulse\Recorders\AgentActionRecorder;

'recorders' => [
    AgentActionRecorder::class => [
        'enabled' => true,
    ],

    // ... Pulse's built-in recorders
],
```

### 4 — Add the card to your dashboard

Publish the dashboard view if you haven't already (`php artisan vendor:publish --tag=pulse-dashboard`), then add:

```blade
<livewire:pulse.ai-actions cols="6" />
```

## What it shows

Per agent class: call count, total cost, average and max latency, and total tokens consumed — pulled from three Pulse-recorded metrics (`ai_action`, `ai_action_duration`, `ai_action_tokens`) fed by an `AgentActionCompleted` event dispatched after every `RunAgentAction::execute()` call.

Cost is computed via [`AgentResult::cost()`](cost-tracking.md), so configure `config('ai-action.pricing')` for accurate figures — an unpriced model records as `$0`.

## Without Pulse installed

Every class involved only ever references Pulse/Livewire symbols inside method bodies, never at the top level, so the package autoloads and runs identically whether or not `laravel/pulse` is installed — the same pattern the [MCP bridge](mcp.md) uses. Nothing is registered unless both the package is installed and `AI_ACTION_PULSE_ENABLED=true`.
