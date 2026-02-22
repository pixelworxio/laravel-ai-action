<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction;

use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Commands\MakeAgentCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service provider for the laravel-ai-action package.
 *
 * Registers the package configuration, binds RunAgentAction as a singleton
 * in the service container, and registers the make:agent Artisan command.
 *
 * The configuration file is published under the "ai-action" tag:
 *   php artisan vendor:publish --tag=ai-action-config
 */
final class LaravelAiActionServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package with its name, config file, and commands.
     *
     * @param Package $package The Spatie package configuration builder.
     * @return void
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ai-action')
            ->hasConfigFile('ai-action')
            ->hasCommands([
                MakeAgentCommand::class,
            ]);
    }

    /**
     * Register package services into the container.
     *
     * Calls the parent register() to process the Spatie configuration, then
     * binds RunAgentAction as a singleton so the same instance is shared
     * across a single request / job lifecycle.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(RunAgentAction::class);
    }
}
