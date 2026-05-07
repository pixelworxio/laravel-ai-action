<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction;

use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Commands\MakeAiActionCommand;
use Pixelworxio\LaravelAiAction\Mcp\Bridge;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service provider for the laravel-ai-action package.
 *
 * Registers the package configuration, binds RunAgentAction as a singleton
 * in the service container, and registers the make:ai-action Artisan command.
 *
 * When both config('ai-action.mcp.enabled') is true AND
 * class_exists(\Laravel\Mcp\Server\Tool::class) is true, the MCP bridge is
 * activated: the Bridge singleton is bound, the AiActionMcp facade is aliased,
 * and a booted() callback flushes all pending tool registrations to the Laravel
 * MCP server after every service provider has booted.
 *
 * When either condition is false the bridge classes are never referenced and
 * PSR-4 lazy autoload keeps them cold — zero runtime overhead for non-MCP users.
 *
 * The configuration file is published under the "ai-action" tag:
 *   php artisan vendor:publish --tag=ai-action-config
 */
final class LaravelAiActionServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package with its name, config file, and commands.
     *
     * @param  Package  $package  The Spatie package configuration builder.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ai-action')
            ->hasConfigFile('ai-action')
            ->hasCommands([
                MakeAiActionCommand::class,
            ]);
    }

    /**
     * Register package services into the container.
     *
     * Calls the parent register() to process the Spatie configuration, then
     * binds RunAgentAction as a singleton so the same instance is shared
     * across a single request / job lifecycle.
     *
     * The MCP Bridge singleton is registered here (not in boot()) so it is
     * available to other service providers' register() calls. String literals
     * are used — not ::class constants — so the optimised classmap does not
     * trigger file loads before the class_exists guard is evaluated.
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton(RunAgentAction::class);

        // String literal intentional: keeps the bridge classmap entry cold
        // until we have confirmed the optional dependency is present.
        if (class_exists('Laravel\\Mcp\\Server\\Tool')) {
            $this->app->singleton(Bridge::class);

            $this->app->alias(
                Bridge::class,
                'ai-action.mcp.bridge',
            );
        }
    }

    /**
     * Boot package services.
     *
     * When the MCP bridge is enabled and laravel/mcp is installed, registers a
     * booted() callback that flushes all pending AiActionMcp::tool() registrations
     * to the Laravel MCP facade. The callback fires after every service provider's
     * boot() has run, so registrations made anywhere during the boot phase are
     * captured before any are committed.
     */
    public function boot(): void
    {
        parent::boot();

        if (
            class_exists('Laravel\\Mcp\\Server\\Tool')
            && (bool) config('ai-action.mcp.enabled', false)
        ) {
            $this->app->booted(function (): void {
                /** @var Bridge $bridge */
                $bridge = $this->app->make(Bridge::class);
                $bridge->flush();
            });
        }
    }
}
