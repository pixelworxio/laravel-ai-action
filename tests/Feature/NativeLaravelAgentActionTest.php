<?php

declare(strict_types=1);

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput as NativeHasStructuredOutput;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Pixelworxio\LaravelAiAction\Adapters\NativeLaravelAgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;

function mockTextAgent(string $responseText = 'Response text'): Agent
{
    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('instructions')->andReturn('You are a test assistant.')->byDefault();
    $agent->shouldReceive('prompt')->andReturn(
        new AgentResponse('inv-001', $responseText, new Usage, new Meta)
    )->byDefault();

    return $agent;
}

function mockStructuredAgent(array $structuredData = ['answer' => 42]): Agent
{
    $agent = Mockery::mock(Agent::class, NativeHasStructuredOutput::class);
    $agent->shouldReceive('instructions')->andReturn('You are a structured assistant.')->byDefault();
    $agent->shouldReceive('schema')->andReturn([]);
    $agent->shouldReceive('prompt')->andReturn(
        new AgentResponse('inv-002', json_encode($structuredData), new Usage, new Meta)
    )->byDefault();

    return $agent;
}

describe('NativeLaravelAgentAction', function (): void {
    it('handle() returns a text AgentResult for a plain Agent', function (): void {
        $action = new NativeLaravelAgentAction(mockTextAgent(), 'Say hello');

        $result = $action->handle(AgentContext::fromRecords([]));

        expect($result->format)->toBe(OutputFormat::Text)
            ->and($result->text)->toBe('Response text')
            ->and($result->structured)->toBeNull();
    });

    it('handle() returns a structured AgentResult for an Agent implementing NativeHasStructuredOutput', function (): void {
        $action = new NativeLaravelAgentAction(mockStructuredAgent(), 'Analyse this');

        $result = $action->handle(AgentContext::fromRecords([]));

        expect($result->format)->toBe(OutputFormat::Structured);
    });

    it('handle() json-encodes the response as text for structured agents', function (): void {
        $agent = mockStructuredAgent(['key' => 'value']);
        $action = new NativeLaravelAgentAction($agent, 'test');

        $result = $action->handle(AgentContext::fromRecords([]));

        $decoded = json_decode($result->text, true);
        expect($decoded)->toBeArray();
    });

    it('instructions() delegates to the native agent', function (): void {
        $action = new NativeLaravelAgentAction(mockTextAgent(), 'ignored');

        expect($action->instructions(AgentContext::fromRecords([])))->toBe('You are a test assistant.');
    });

    it('prompt() returns the user prompt passed to the constructor', function (): void {
        $action = new NativeLaravelAgentAction(mockTextAgent(), 'My prompt here');

        expect($action->prompt(AgentContext::fromRecords([])))->toBe('My prompt here');
    });

    it('provider() and model() return values from config', function (): void {
        $action = new NativeLaravelAgentAction(mockTextAgent(), 'test');

        expect($action->provider())->toBe('anthropic')
            ->and($action->model())->toBe('claude-sonnet-4-20250514');
    });

    it('structured result inputTokens and outputTokens are zero', function (): void {
        $action = new NativeLaravelAgentAction(mockStructuredAgent(), 'test');

        $result = $action->handle(AgentContext::fromRecords([]));

        expect($result->inputTokens)->toBe(0)
            ->and($result->outputTokens)->toBe(0);
    });
});
