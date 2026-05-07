<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Mcp;

use Laravel\Mcp\Response;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;

/**
 * Maps an AgentResult to one or more Laravel MCP Response objects.
 *
 * This is the single translation point between the package's result model and
 * the MCP protocol's content model. Format mapping:
 *
 *  - OutputFormat::Text       → Response::text($result->text)
 *  - OutputFormat::Markdown   → Response::text($result->text)  (MCP clients render markdown in text)
 *  - OutputFormat::Structured → Response::text(json_encode($result->structured ?? decoded text))
 *
 * Token counts and provider/model metadata are attached via withMeta() so MCP
 * clients that surface metadata get observability for free.
 *
 * If the action implements a custom formatMcpResponse() method the responder
 * defers to it entirely, enabling bespoke multi-content responses (e.g. image
 * + text) without polluting the core contract.
 */
class AgentResultResponder
{
    /**
     * Translate an AgentResult into an MCP Response (or array of Responses).
     *
     * @param  AgentResult  $result  The result from RunAgentAction::execute().
     * @param  AgentAction  $action  The action that produced the result.
     * @return Response|array<int, Response>
     */
    public function respond(AgentResult $result, AgentAction $action): Response|array
    {
        if (method_exists($action, 'formatMcpResponse')) {
            /** @var Response|array<int, Response> $custom */
            $custom = $action->formatMcpResponse($result);

            return $custom;
        }

        $response = $this->buildResponse($result);

        return $response->withMeta([
            'ai-action' => [
                'provider' => $result->provider,
                'model' => $result->model,
                'input_tokens' => $result->inputTokens,
                'output_tokens' => $result->outputTokens,
            ],
        ]);
    }

    /**
     * Build the base Response from the result's format and content.
     *
     * Structured results are serialised to a JSON text response so this method
     * consistently returns Response (not ResponseFactory). Callers that need a
     * true MCP structured-content envelope should implement formatMcpResponse()
     * on the action and return Response::structured() directly from there.
     */
    private function buildResponse(AgentResult $result): Response
    {
        return match ($result->format) {
            OutputFormat::Structured => Response::text(
                (string) json_encode(
                    $this->resolveStructuredData($result),
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ),
            ),
            OutputFormat::Text, OutputFormat::Markdown => Response::text($result->text),
        };
    }

    /**
     * Resolve the structured payload, falling back to JSON-decoding the text.
     *
     * $result->structured is set when HasStructuredOutput::mapOutput() ran.
     * If it is null (shouldn't happen for Structured format, but defensively
     * handled), we decode the raw text as a fallback.
     *
     * @return array<string, mixed>
     */
    private function resolveStructuredData(AgentResult $result): array
    {
        if (is_array($result->structured)) {
            return $result->structured;
        }

        $decoded = json_decode($result->text, true);

        return is_array($decoded) ? $decoded : ['text' => $result->text];
    }
}
