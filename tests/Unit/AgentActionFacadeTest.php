<?php

declare(strict_types=1);

use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Facades\AgentAction;

describe('AgentAction facade', function (): void {
    it('resolves to the RunAgentAction singleton from the container', function (): void {
        $resolved = app()->make(AgentAction::getFacadeRoot()::class);

        expect($resolved)->toBeInstanceOf(RunAgentAction::class);
    });

    it('facade root is the RunAgentAction singleton', function (): void {
        expect(AgentAction::getFacadeRoot())->toBeInstanceOf(RunAgentAction::class);
    });
});
