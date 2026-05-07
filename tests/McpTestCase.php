<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Tests;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Foundation\Application;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Mcp\Server\Tool;
use Pixelworxio\LaravelAiAction\Mcp\Bridge;

/**
 * Base test case for MCP bridge tests.
 *
 * Loads the MCP stub classes when the real laravel/mcp package is absent, so
 * bridge tests can run in both environments. Also enables the MCP config flag
 * so the service provider's booted() path is exercisable.
 */
class McpTestCase extends TestCase
{
    /**
     * Boot MCP stubs when the real package is not installed.
     */
    protected function setUp(): void
    {
        if (! class_exists(Tool::class)) {
            require_once __DIR__.'/Fixtures/Mcp/bootstrap.php';
        }

        parent::setUp();

        config(['ai-action.mcp.enabled' => true]);

        app(Bridge::class)->reset();

        app()->bind(
            JsonSchema::class,
            JsonSchemaTypeFactory::class,
        );
    }

    /**
     * Define the application environment for MCP testing.
     *
     * @param  Application  $app
     */
    public function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('ai-action.mcp.enabled', true);
        $app['config']->set('ai-action.mcp.discover_in', []);
        $app['config']->set('ai-action.mcp.cache_discovery', false);
    }
}
