<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Pixelworxio\LaravelAiAction\LaravelAiActionServiceProvider;

/**
 * Base test case for the laravel-ai-action package.
 *
 * Bootstraps the Laravel application via Orchestra Testbench and registers
 * the LaravelAiActionServiceProvider so that all package bindings, config,
 * and commands are available during testing.
 */
class TestCase extends Orchestra
{
    /**
     * Boot the application after all providers have been registered.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Return the package service providers that should be loaded.
     *
     * @param  Application  $app  The application instance.
     * @return list<class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelAiActionServiceProvider::class,
        ];
    }

    /**
     * Define the application environment for testing.
     *
     * @param  Application  $app  The application instance.
     */
    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('ai-action.provider', 'anthropic');
        $app['config']->set('ai-action.model', 'claude-sonnet-4-20250514');
        $app['config']->set('ai-action.queue', 'default');
        $app['config']->set('ai-action.max_tokens', 2048);
        $app['config']->set('ai-action.logging', false);
    }
}
