<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Adapters;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput as NativeHasStructuredOutput;
use Pixelworxio\LaravelAiAction\Concerns\InteractsWithAgent;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;

final class NativeLaravelAgentAction implements AgentAction
{
    use InteractsWithAgent;

    public function __construct(
        private readonly Agent $agent,
        private readonly string $userPrompt,
    ) {}

    public function instructions(AgentContext $context): string
    {
        return (string) $this->agent->instructions();
    }

    public function prompt(AgentContext $context): string
    {
        return $this->userPrompt;
    }

    public function handle(AgentContext $context): AgentResult
    {
        // Delegate directly to the native agent's own prompt() call,
        // bypassing RunAgentAction's AnonymousAgent construction entirely.
        if ($this->agent instanceof NativeHasStructuredOutput) {
            $response = $this->agent->prompt($this->userPrompt);

            return new AgentResult(
                text: json_encode($response, JSON_THROW_ON_ERROR),
                format: OutputFormat::Structured,
                structured: $response,
                inputTokens: 0,
                outputTokens: 0,
                provider: $this->provider(),
                model: $this->model(),
                metadata: [],
            );
        }

        $text = $this->agent->prompt($this->userPrompt);

        return new AgentResult(
            text: (string) $text,
            format: OutputFormat::Text,
            structured: null,
            inputTokens: 0,
            outputTokens: 0,
            provider: $this->provider(),
            model: $this->model(),
            metadata: [],
        );
    }
}