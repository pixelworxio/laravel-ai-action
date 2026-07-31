<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | The default provider to use when running AI actions. This value should
    | correspond to one of the configured providers in config/ai.php.
    |
    */
    'provider' => env('AI_ACTION_PROVIDER', 'anthropic'),

    /*
    |--------------------------------------------------------------------------
    | Default AI Model
    |--------------------------------------------------------------------------
    |
    | The default model to use when running AI actions. This can be overridden
    | per-agent by implementing the model() method on the agent class.
    |
    */
    'model' => env('AI_ACTION_MODEL', 'claude-sonnet-4-20250514'),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | The queue to use when dispatching queued AI actions via RunAgentActionJob.
    |
    */
    'queue' => env('AI_ACTION_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Maximum Tokens
    |--------------------------------------------------------------------------
    |
    | The maximum number of tokens to generate in a single AI action response.
    |
    */
    'max_tokens' => env('AI_ACTION_MAX_TOKENS', 2048),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, each agent invocation will be logged including the provider,
    | model, prompt, and result metadata.
    |
    */
    'logging' => env('AI_ACTION_LOGGING', false),

    /*
    |--------------------------------------------------------------------------
    | MCP Bridge
    |--------------------------------------------------------------------------
    |
    | When enabled (and laravel/mcp is installed), agent actions that implement
    | ExposedAsMcpTool can be registered as MCP tools via the AiActionMcp
    | facade in your service provider or routes/ai.php. Auto-discovery scans
    | the paths listed in discover_in for classes carrying #[ExposesAsMcpTool].
    |
    */
    'mcp' => [
        'enabled' => env('AI_ACTION_MCP_ENABLED', false),
        'discover_in' => [],
        'cache_discovery' => env('AI_ACTION_MCP_CACHE_DISCOVERY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    |
    | Per-million-token USD rates used by AgentResult::cost() to compute the
    | cost of a single invocation. Keyed by provider, then exact model
    | identifier. Rates below are illustrative and change frequently — check
    | your provider's current pricing page before relying on these for
    | budgeting. A missing provider/model pair makes cost() return null
    | rather than a misleading 0.0.
    |
    */
    'pricing' => [
        'anthropic' => [
            'claude-opus-4-20250514' => ['input' => 15.00, 'output' => 75.00],
            'claude-sonnet-4-20250514' => ['input' => 3.00, 'output' => 15.00],
            'claude-haiku-4-20250514' => ['input' => 0.80, 'output' => 4.00],
        ],
        'openai' => [
            'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
            'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Pulse Integration
    |--------------------------------------------------------------------------
    |
    | When enabled (and laravel/pulse is installed), every agent invocation is
    | recorded to Pulse via the bundled AgentActionRecorder, powering the
    | <livewire:pulse.ai-actions /> card. See docs/pulse.md for setup.
    |
    */
    'pulse' => [
        'enabled' => env('AI_ACTION_PULSE_ENABLED', false),
    ],
];
