<?php

declare(strict_types=1);

use Pixelworxio\LaravelAiAction\Events\AgentActionCompleted;
use Pixelworxio\LaravelAiAction\Pulse\Recorders\AgentActionRecorder;

/*
|--------------------------------------------------------------------------
| Pulse recorder tests
|--------------------------------------------------------------------------
|
| laravel/pulse is not a require-dev dependency (its first-party storage
| requires MySQL/MariaDB/PostgreSQL, which this package's test suite does
| not provision). AgentActionRecorder::record() itself is therefore not
| exercised here — it would fatal without the real Pulse facade. These
| tests only verify the class autoloads safely and declares the contract
| Pulse's recorder discovery expects; run a manual check with laravel/pulse
| installed before releasing changes to this file.
|
*/

describe('AgentActionRecorder', function (): void {
    it('listens for AgentActionCompleted', function (): void {
        $recorder = new AgentActionRecorder;

        expect($recorder->listen)->toBe([AgentActionCompleted::class]);
    });

    it('declares a record() method accepting AgentActionCompleted', function (): void {
        $reflection = new ReflectionMethod(AgentActionRecorder::class, 'record');
        $parameter = $reflection->getParameters()[0];

        expect($reflection->getNumberOfParameters())->toBe(1)
            ->and((string) $parameter->getType())->toBe(AgentActionCompleted::class);
    });
});
