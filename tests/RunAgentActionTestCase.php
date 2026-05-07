<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\AiServiceProvider;

/**
 * Test case for RunAgentAction direct execution tests.
 *
 * Extends the base TestCase and additionally registers the Laravel AI
 * AiServiceProvider so that AiManager is available for agent faking
 * (AnonymousAgent::fake(), StructuredAnonymousAgent::fake(), etc.).
 */
class RunAgentActionTestCase extends TestCase
{
    /**
     * @param  Application  $app
     * @return list<class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AiServiceProvider::class,
        ];
    }
}
