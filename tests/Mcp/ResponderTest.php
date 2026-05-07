<?php

declare(strict_types=1);

use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;
use Pixelworxio\LaravelAiAction\Mcp\AgentResultResponder;
use Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions\StubMcpAction;
use Pixelworxio\LaravelAiAction\Tests\McpTestCase;

uses(McpTestCase::class)->group('mcp');

if (! class_exists(Tool::class)) {
    require_once __DIR__.'/../Fixtures/Mcp/bootstrap.php';
}

/**
 * @group mcp
 */
describe('AgentResultResponder', function (): void {
    beforeEach(function (): void {
        $this->responder = new AgentResultResponder;
        $this->action = new StubMcpAction;
    });

    it('maps Text format to Response::text', function (): void {
        $result = new AgentResult(
            text: 'Hello world',
            format: OutputFormat::Text,
            structured: null,
            inputTokens: 10,
            outputTokens: 5,
            provider: 'anthropic',
            model: 'claude-3',
            metadata: [],
        );

        $response = $this->responder->respond($result, $this->action);

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->content()->toArray()['type'])->toBe('text');
        expect((string) $response->content())->toBe('Hello world');
    });

    it('maps Markdown format to Response::text', function (): void {
        $result = new AgentResult(
            text: '# Heading',
            format: OutputFormat::Markdown,
            structured: null,
            inputTokens: 5,
            outputTokens: 3,
            provider: 'anthropic',
            model: 'claude-3',
            metadata: [],
        );

        $response = $this->responder->respond($result, $this->action);

        expect($response->content()->toArray()['type'])->toBe('text');
        expect((string) $response->content())->toBe('# Heading');
    });

    it('maps Structured format with array data to a JSON text Response', function (): void {
        $result = new AgentResult(
            text: '{"summary":"ok"}',
            format: OutputFormat::Structured,
            structured: ['summary' => 'ok'],
            inputTokens: 20,
            outputTokens: 10,
            provider: 'anthropic',
            model: 'claude-3',
            metadata: [],
        );

        $response = $this->responder->respond($result, $this->action);

        expect($response->content()->toArray()['type'])->toBe('text');
        expect(json_decode((string) $response->content(), true))->toBe(['summary' => 'ok']);
    });

    it('falls back to decoding text when structured is null', function (): void {
        $result = new AgentResult(
            text: '{"decoded":true}',
            format: OutputFormat::Structured,
            structured: null,
            inputTokens: 5,
            outputTokens: 5,
            provider: 'anthropic',
            model: 'claude-3',
            metadata: [],
        );

        $response = $this->responder->respond($result, $this->action);

        expect($response->content()->toArray()['type'])->toBe('text');
        expect(json_decode((string) $response->content(), true))->toBe(['decoded' => true]);
    });

    it('attaches ai-action metadata via withMeta', function (): void {
        $result = new AgentResult(
            text: 'ok',
            format: OutputFormat::Text,
            structured: null,
            inputTokens: 42,
            outputTokens: 7,
            provider: 'openai',
            model: 'gpt-4o',
            metadata: [],
        );

        $response = $this->responder->respond($result, $this->action);
        $meta = $response->content()->toArray()['_meta'] ?? [];

        expect($meta)->toHaveKey('ai-action');
        expect($meta['ai-action']['provider'])->toBe('openai');
        expect($meta['ai-action']['model'])->toBe('gpt-4o');
        expect($meta['ai-action']['input_tokens'])->toBe(42);
        expect($meta['ai-action']['output_tokens'])->toBe(7);
    });

    it('defers to formatMcpResponse when the action implements it', function (): void {
        $customResponse = Response::text('custom');

        $action = new class extends StubMcpAction
        {
            public function formatMcpResponse(AgentResult $result): Response
            {
                return Response::text('custom');
            }
        };

        $result = new AgentResult(
            text: 'ignored',
            format: OutputFormat::Text,
            structured: null,
            inputTokens: 0,
            outputTokens: 0,
            provider: 'fake',
            model: 'fake',
            metadata: [],
        );

        $response = (new AgentResultResponder)->respond($result, $action);

        expect((string) $response->content())->toBe('custom');
    });
});
