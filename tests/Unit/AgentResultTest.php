<?php

declare(strict_types=1);

use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;

describe('AgentResult', function (): void {
    function makeTextResult(string $text = 'Hello'): AgentResult
    {
        return new AgentResult(
            text: $text,
            format: OutputFormat::Text,
            structured: null,
            inputTokens: 10,
            outputTokens: 20,
            provider: 'anthropic',
            model: 'claude-sonnet-4-20250514',
            metadata: ['invocation_id' => 'test-123'],
        );
    }

    function makeStructuredResult(mixed $structured = ['key' => 'val']): AgentResult
    {
        return new AgentResult(
            text: '{"key":"val"}',
            format: OutputFormat::Structured,
            structured: $structured,
            inputTokens: 5,
            outputTokens: 15,
            provider: 'openai',
            model: 'gpt-4o',
            metadata: [],
        );
    }

    describe('isStructured()', function (): void {
        it('returns false for text format', function (): void {
            expect(makeTextResult()->isStructured())->toBeFalse();
        });

        it('returns true for structured format', function (): void {
            expect(makeStructuredResult()->isStructured())->toBeTrue();
        });

        it('returns false for markdown format', function (): void {
            $result = new AgentResult(
                text: '# Title',
                format: OutputFormat::Markdown,
                structured: null,
                inputTokens: 1,
                outputTokens: 2,
                provider: 'anthropic',
                model: 'claude-haiku-4-5-20251001',
                metadata: [],
            );

            expect($result->isStructured())->toBeFalse();
        });
    });

    describe('toArray()', function (): void {
        it('contains all expected keys for a text result', function (): void {
            $array = makeTextResult('Hello world')->toArray();

            expect($array)->toHaveKeys([
                'text', 'format', 'structured', 'input_tokens',
                'output_tokens', 'provider', 'model', 'metadata',
            ])
                ->and($array['text'])->toBe('Hello world')
                ->and($array['format'])->toBe('Text')
                ->and($array['structured'])->toBeNull()
                ->and($array['input_tokens'])->toBe(10)
                ->and($array['output_tokens'])->toBe(20)
                ->and($array['provider'])->toBe('anthropic')
                ->and($array['model'])->toBe('claude-sonnet-4-20250514');
        });

        it('serializes the format enum name', function (): void {
            expect(makeStructuredResult()->toArray()['format'])->toBe('Structured');
        });

        it('includes structured data', function (): void {
            $data = ['items' => [1, 2, 3]];
            $result = makeStructuredResult($data);

            expect($result->toArray()['structured'])->toBe($data);
        });
    });

    describe('readonly enforcement', function (): void {
        it('is a readonly final class', function (): void {
            $reflection = new ReflectionClass(AgentResult::class);

            expect($reflection->isFinal())->toBeTrue()
                ->and($reflection->isReadOnly())->toBeTrue();
        });

        it('exposes all constructor properties publicly', function (): void {
            $result = makeTextResult('test');

            expect($result->text)->toBe('test')
                ->and($result->format)->toBe(OutputFormat::Text)
                ->and($result->inputTokens)->toBe(10)
                ->and($result->outputTokens)->toBe(20)
                ->and($result->provider)->toBe('anthropic')
                ->and($result->model)->toBe('claude-sonnet-4-20250514');
        });
    });
});
