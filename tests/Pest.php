<?php

declare(strict_types=1);

use Pixelworxio\LaravelAiAction\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Suite Bootstrap
|--------------------------------------------------------------------------
|
| This file bootstraps Pest for the laravel-ai-action package. The base
| TestCase covers all Feature and Unit tests. MCP bridge tests additionally
| extend McpTestCase, which loads the MCP fixture stubs when laravel/mcp is
| absent and enables the bridge config.
|
| CI matrix:
|   - Standard lane: vendor/bin/pest
|   - No-MCP lane:   vendor/bin/pest --exclude-group=mcp
|
*/

// Load MCP stubs before any test file is parsed so that bridge classes whose
// declarations extend Laravel\Mcp\* (e.g. AgentActionServer extends Server)
// can be autoloaded safely in the no-MCP CI lane.
if (! class_exists(\Laravel\Mcp\Server\Tool::class)) {
    require_once __DIR__.'/Fixtures/Mcp/bootstrap.php';
}

pest()->extend(TestCase::class)
    ->in('Feature', 'Unit');
