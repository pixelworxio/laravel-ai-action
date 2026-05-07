<?php

declare(strict_types=1);

use Pixelworxio\LaravelAiAction\Concerns\InteractsWithAgent;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;

function makeAgentWithTrait(): AgentAction
{
    return new class implements AgentAction
    {
        use InteractsWithAgent;

        public function instructions(AgentContext $context): string
        {
            return '';
        }

        public function prompt(AgentContext $context): string
        {
            return '';
        }

        public function handle(AgentContext $context): AgentResult
        {
            return new AgentResult('', OutputFormat::Text, null, 0, 0, $this->provider(), $this->model(), []);
        }
    };
}

describe('InteractsWithAgent', function (): void {
    it('provider() returns the ai-action.provider config value', function (): void {
        // TestCase sets this to 'anthropic' in getEnvironmentSetUp()
        expect(makeAgentWithTrait()->provider())->toBe('anthropic');
    });

    it('provider() returns an overridden value from config', function (): void {
        app('config')->set('ai-action.provider', 'openai');

        expect(makeAgentWithTrait()->provider())->toBe('openai');
    });

    it('model() returns the ai-action.model config value', function (): void {
        // TestCase sets this to 'claude-sonnet-4-20250514' in getEnvironmentSetUp()
        expect(makeAgentWithTrait()->model())->toBe('claude-sonnet-4-20250514');
    });

    it('model() returns an overridden value from config', function (): void {
        app('config')->set('ai-action.model', 'gpt-4o');

        expect(makeAgentWithTrait()->model())->toBe('gpt-4o');
    });

    it('provider() defaults to anthropic when the key is missing', function (): void {
        app('config')->offsetUnset('ai-action');

        expect(makeAgentWithTrait()->provider())->toBe('anthropic');
    });

    it('model() defaults to claude-sonnet-4-20250514 when the key is missing', function (): void {
        app('config')->offsetUnset('ai-action');

        expect(makeAgentWithTrait()->model())->toBe('claude-sonnet-4-20250514');
    });
});
