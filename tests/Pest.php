<?php

declare(strict_types=1);

use Pixelworxio\LaravelAiAction\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Suite Bootstrap
|--------------------------------------------------------------------------
|
| This file bootstraps Pest for the laravel-ai-action package. Both the
| Feature and Unit suites use the shared TestCase so that the full Laravel
| application (via Orchestra Testbench) is available in every test.
|
*/

pest()->extend(TestCase::class)
    ->in('Feature', 'Unit');
