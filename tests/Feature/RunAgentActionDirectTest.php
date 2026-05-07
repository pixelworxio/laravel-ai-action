<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Laravel\Ai\AiServiceProvider;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\StructuredAnonymousAgent;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Contracts\HasStreamingResponse;
use Pixelworxio\LaravelAiAction\Contracts\HasStructuredOutput;
use Pixelworxio\LaravelAiAction\Contracts\HasTools;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;
use Pixelworxio\LaravelAiAction\Exceptions\AgentException;

// ---------------------------------------------------------------------------
// Minimal AI provider config so AnonymousAgent::fake() works
// ---------------------------------------------------------------------------

function setupAiConfig(): void
{
    config([
        'ai.default' => 'anthropic',
        'ai.providers.anthropic' => [
            'driver' => 'anthropic',
            'key' => 'fake-key',
            'name' => 'anthropic',
        ],
    ]);
}

// ---------------------------------------------------------------------------
// Fixture agents
// ---------------------------------------------------------------------------

function makeDirectTextAgent(): AgentAction
{
    return new class implements AgentAction
    {
        public function instructions(AgentContext $context): string
        {
            return 'You help with testing.';
        }

        public function prompt(AgentContext $context): string
        {
            return 'Say hello.';
        }

        public function provider(): string
        {
            return 'anthropic';
        }

        public function model(): string
        {
            return 'claude-sonnet-4-20250514';
        }

        public function handle(AgentContext $context): AgentResult
        {
            return app(RunAgentAction::class)->execute($this, $context);
        }
    };
}

function makeDirectToolsAgent(): AgentAction
{
    return new class implements AgentAction, HasTools
    {
        public function instructions(AgentContext $context): string
        {
            return 'You help with testing.';
        }

        public function prompt(AgentContext $context): string
        {
            return 'Use a tool.';
        }

        public function provider(): string
        {
            return 'anthropic';
        }

        public function model(): string
        {
            return 'claude-sonnet-4-20250514';
        }

        public function tools(): array
        {
            return [];
        }

        public function handle(AgentContext $context): AgentResult
        {
            return app(RunAgentAction::class)->execute($this, $context);
        }
    };
}

function makeDirectStreamingAgent(array &$chunks = [], bool $continueStreaming = true): AgentAction
{
    return new class($chunks, $continueStreaming) implements AgentAction, HasStreamingResponse
    {
        private array $collected = [];

        private ?AgentResult $completedResult = null;

        public function __construct(private array &$chunks, private bool $continueStreaming) {}

        public function instructions(AgentContext $context): string
        {
            return 'Stream this.';
        }

        public function prompt(AgentContext $context): string
        {
            return 'Stream hello.';
        }

        public function provider(): string
        {
            return 'anthropic';
        }

        public function model(): string
        {
            return 'claude-sonnet-4-20250514';
        }

        public function onChunk(string $chunk): bool
        {
            $this->chunks[] = $chunk;

            return $this->continueStreaming;
        }

        public function onComplete(AgentResult $result): void
        {
            $this->completedResult = $result;
        }

        public function getCompleted(): ?AgentResult
        {
            return $this->completedResult;
        }

        public function handle(AgentContext $context): AgentResult
        {
            return app(RunAgentAction::class)->execute($this, $context);
        }
    };
}

function makeDirectStructuredAgent(): AgentAction
{
    return new class implements AgentAction, HasStructuredOutput
    {
        public function instructions(AgentContext $context): string
        {
            return 'Return structured data.';
        }

        public function prompt(AgentContext $context): string
        {
            return 'Give me data.';
        }

        public function provider(): string
        {
            return 'anthropic';
        }

        public function model(): string
        {
            return 'claude-sonnet-4-20250514';
        }

        public function outputSchema(): array
        {
            return [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'count' => ['type' => 'integer'],
                ],
                'required' => ['name', 'count'],
            ];
        }

        public function mapOutput(array $raw): mixed
        {
            return $raw;
        }

        public function handle(AgentContext $context): AgentResult
        {
            return app(RunAgentAction::class)->execute($this, $context);
        }
    };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('RunAgentAction (direct execution)', function (): void {
    beforeEach(function (): void {
        $this->app->register(AiServiceProvider::class);
        setupAiConfig();
    });

    it('executes text mode and returns an AgentResult with text format', function (): void {
        AnonymousAgent::fake(['Hello from fake']);

        $runner = app(RunAgentAction::class);
        $result = $runner->execute(makeDirectTextAgent(), AgentContext::fromRecords([]));

        expect($result)->toBeInstanceOf(AgentResult::class)
            ->and($result->format)->toBe(OutputFormat::Text)
            ->and($result->text)->toBe('Hello from fake')
            ->and($result->structured)->toBeNull();
    });

    it('executes text mode with HasTools agent using the tools', function (): void {
        AnonymousAgent::fake(['Tool response']);

        $result = app(RunAgentAction::class)->execute(makeDirectToolsAgent(), AgentContext::fromRecords([]));

        expect($result->format)->toBe(OutputFormat::Text)
            ->and($result->text)->toBe('Tool response');
    });

    it('executes streaming mode and calls onChunk for each delta', function (): void {
        AnonymousAgent::fake(['Hello World']);
        $chunks = [];
        $agent = makeDirectStreamingAgent($chunks);

        $result = app(RunAgentAction::class)->execute($agent, AgentContext::fromRecords([]));

        expect($result->format)->toBe(OutputFormat::Text)
            ->and(count($chunks))->toBeGreaterThan(0);
    });

    it('streaming mode calls onComplete with the final result', function (): void {
        AnonymousAgent::fake(['Done streaming']);
        $chunks = [];
        $agent = makeDirectStreamingAgent($chunks);

        app(RunAgentAction::class)->execute($agent, AgentContext::fromRecords([]));

        expect($agent->getCompleted())->toBeInstanceOf(AgentResult::class);
    });

    it('streaming mode halts when onChunk returns false', function (): void {
        AnonymousAgent::fake(['Word1 Word2 Word3 Word4 Word5']);
        $chunks = [];
        $agent = makeDirectStreamingAgent($chunks, continueStreaming: false);

        $result = app(RunAgentAction::class)->execute($agent, AgentContext::fromRecords([]));

        expect($result)->toBeInstanceOf(AgentResult::class)
            ->and(count($chunks))->toBe(1);
    });

    it('executes structured mode and returns structured format', function (): void {
        StructuredAnonymousAgent::fake([['name' => 'Test', 'count' => 5]]);

        $result = app(RunAgentAction::class)->execute(makeDirectStructuredAgent(), AgentContext::fromRecords([]));

        expect($result->format)->toBe(OutputFormat::Structured)
            ->and($result->structured)->toBeArray()
            ->and($result->structured)->toHaveKey('name');
    });

    it('wraps a non-AgentException in AgentException when agent methods throw', function (): void {
        $agent = new class implements AgentAction
        {
            public function instructions(AgentContext $context): string
            {
                return '';
            }

            public function prompt(AgentContext $context): string
            {
                throw new RuntimeException('Prompt generation failed', 42);
            }

            public function provider(): string
            {
                return 'anthropic';
            }

            public function model(): string
            {
                return 'claude-sonnet-4-20250514';
            }

            public function handle(AgentContext $context): AgentResult
            {
                return app(RunAgentAction::class)->execute($this, $context);
            }
        };

        AnonymousAgent::fake(['fallback']);

        expect(fn () => app(RunAgentAction::class)->execute($agent, AgentContext::fromRecords([])))
            ->toThrow(AgentException::class);
    });

    it('re-throws AgentException without double-wrapping', function (): void {
        $agentException = null;

        $agent = new class implements AgentAction
        {
            public function instructions(AgentContext $context): string
            {
                return '';
            }

            public function prompt(AgentContext $context): string
            {
                throw new AgentException($this, 'Already an AgentException');
            }

            public function provider(): string
            {
                return 'anthropic';
            }

            public function model(): string
            {
                return 'claude-sonnet-4-20250514';
            }

            public function handle(AgentContext $context): AgentResult
            {
                return app(RunAgentAction::class)->execute($this, $context);
            }
        };

        AnonymousAgent::fake(['fallback']);

        $thrown = null;
        try {
            app(RunAgentAction::class)->execute($agent, AgentContext::fromRecords([]));
        } catch (AgentException $e) {
            $thrown = $e;
        }

        expect($thrown)->toBeInstanceOf(AgentException::class)
            ->and($thrown->getMessage())->toBe('Already an AgentException')
            ->and($thrown->getPrevious())->toBeNull();
    });

    it('logs execution info when ai-action.logging is true', function (): void {
        AnonymousAgent::fake(['Logged response']);
        config(['ai-action.logging' => true]);
        Log::shouldReceive('info')
            ->once()
            ->with('ai-action.executed', Mockery::on(fn ($data) => isset($data['agent'])));

        app(RunAgentAction::class)->execute(makeDirectTextAgent(), AgentContext::fromRecords([]));
    });

    it('does not log when ai-action.logging is false', function (): void {
        AnonymousAgent::fake(['Quiet response']);
        config(['ai-action.logging' => false]);
        Log::shouldReceive('info')->never();

        app(RunAgentAction::class)->execute(makeDirectTextAgent(), AgentContext::fromRecords([]));
    });

    it('handles complex nested schema types covering object, array, boolean, number, and enum branches', function (): void {
        StructuredAnonymousAgent::fake([['name' => 'active', 'active' => true, 'tags' => ['a'], 'ratio' => 1.5, 'meta' => ['key' => 'v']]]);

        $agent = new class implements AgentAction, HasStructuredOutput
        {
            public function instructions(AgentContext $context): string
            {
                return 'complex schema';
            }

            public function prompt(AgentContext $context): string
            {
                return 'give complex data';
            }

            public function provider(): string
            {
                return 'anthropic';
            }

            public function model(): string
            {
                return 'claude-sonnet-4-20250514';
            }

            public function outputSchema(): array
            {
                return [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'enum' => ['active', 'inactive']],
                        'active' => ['type' => 'boolean'],
                        'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'ratio' => ['type' => 'number'],
                        'meta' => [
                            'type' => 'object',
                            'properties' => ['key' => ['type' => 'string']],
                            'required' => ['key'],
                        ],
                    ],
                    'required' => ['name'],
                ];
            }

            public function mapOutput(array $raw): mixed
            {
                return $raw;
            }

            public function handle(AgentContext $context): AgentResult
            {
                return app(RunAgentAction::class)->execute($this, $context);
            }
        };

        $result = app(RunAgentAction::class)->execute($agent, AgentContext::fromRecords([]));

        expect($result->format)->toBe(OutputFormat::Structured);
    });
});
