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
];
