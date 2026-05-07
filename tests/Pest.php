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

pest()->extend(TestCase::class)
    ->in('Feature', 'Unit');
