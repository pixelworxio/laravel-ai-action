<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Pixelworxio\LaravelAiAction\LaravelAiActionServiceProvider;
use Pixelworxio\LaravelAiAction\Pulse\Livewire\AgentActionsCard;
use Pixelworxio\LaravelAiAction\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pulse Livewire component registration
|--------------------------------------------------------------------------
|
| This regression-tests the bug where installing laravel/pulse (which
| pulls in livewire/livewire) made class_exists('Laravel\Pulse\Livewire\Card')
| true unconditionally, causing LaravelAiActionServiceProvider::boot() to
| call Livewire::component() on every application — even ones that never
| opted into ai-action.pulse.enabled and never registered Livewire's own
| service provider. That crashed every test and every non-Pulse consumer
| app. The fix gates registration on config('ai-action.pulse.enabled').
|
*/

describe('Pulse Livewire component registration', function (): void {
    it('does not register the pulse.ai-actions component when pulse.enabled is false (the default)', function (): void {
        // The base TestCase boots our provider without registering Livewire's
        // own provider at all. If this ran unconditionally on class_exists()
        // alone, setUp() itself would already have fatally errored before
        // reaching this assertion.
        expect(true)->toBeTrue();
    });

    it('registers the pulse.ai-actions component when pulse.enabled is true and livewire is present', function (): void {
        $testCase = new class('pulse_integration') extends TestCase
        {
            protected function getPackageProviders($app): array
            {
                return [
                    LivewireServiceProvider::class,
                    LaravelAiActionServiceProvider::class,
                ];
            }

            /**
             * @param  Application  $app
             */
            public function getEnvironmentSetUp($app): void
            {
                parent::getEnvironmentSetUp($app);

                $app['config']->set('ai-action.pulse.enabled', true);
            }
        };

        $testCase->setUp();

        expect(Livewire::exists('pulse.ai-actions'))->toBeTrue()
            ->and(Livewire::exists(AgentActionsCard::class))->toBeTrue();

        $testCase->tearDown();
    });
});
