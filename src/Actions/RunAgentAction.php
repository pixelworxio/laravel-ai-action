<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Actions;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\StructuredAnonymousAgent;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Contracts\HasStreamingResponse;
use Pixelworxio\LaravelAiAction\Contracts\HasStructuredOutput;
use Pixelworxio\LaravelAiAction\Contracts\HasTools;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;
use Pixelworxio\LaravelAiAction\Exceptions\AgentException;

/**
 * Orchestrates the execution of an AgentAction against the Laravel AI SDK.
 *
 * RunAgentAction is the single entry point for running any agent action. It
 * inspects the agent for optional capability interfaces (HasTools,
 * HasStructuredOutput, HasStreamingResponse) and selects the appropriate
 * execution branch accordingly. All provider calls are wrapped to surface
 * clear AgentException instances on failure.
 *
 * Not declared final to allow FakeAgentAction to extend it in tests.
 */
class RunAgentAction
{
    /**
     * Execute the given agent action and return a typed AgentResult.
     *
     * Execution strategy (checked in priority order):
     * 1. HasStructuredOutput — structured JSON schema mode.
     * 2. HasStreamingResponse — streaming mode with chunk callbacks.
     * 3. Default — standard synchronous text generation.
     *
     * When HasTools is also implemented the tools are registered regardless
     * of which of the above branches is taken.
     *
     * @param  AgentAction  $agent  The agent action to execute.
     * @param  AgentContext  $context  The runtime context for the invocation.
     * @return AgentResult The typed result wrapping the AI response.
     *
     * @throws AgentException When the AI provider call fails.
     */
    public function execute(AgentAction $agent, AgentContext $context): AgentResult
    {
        try {
            $result = match (true) {
                $agent instanceof HasStructuredOutput => $this->executeStructured($agent, $context),
                $agent instanceof HasStreamingResponse => $this->executeStreaming($agent, $context),
                default => $this->executeText($agent, $context),
            };
        } catch (AgentException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw AgentException::fromThrowable($agent, $e);
        }

        if ((bool) config('ai-action.logging', false)) {
            Log::info('ai-action.executed', [
                'agent' => $agent::class,
                'provider' => $result->provider,
                'model' => $result->model,
                'input_tokens' => $result->inputTokens,
                'output_tokens' => $result->outputTokens,
            ]);
        }

        return $result;
    }

    /**
     * Execute the agent in standard text-generation mode.
     *
     * @param  AgentAction  $agent  The agent action.
     * @param  AgentContext  $context  The runtime context.
     * @return AgentResult The text result.
     */
    private function executeText(AgentAction $agent, AgentContext $context): AgentResult
    {
        $sdkAgent = $this->buildAnonymousAgent($agent, $context);

        $response = $sdkAgent->prompt(
            prompt: $agent->prompt($context),
            provider: $agent->provider(),
            model: $agent->model(),
        );

        return new AgentResult(
            text: $response->text,
            format: OutputFormat::Text,
            structured: null,
            inputTokens: $response->usage->promptTokens,
            outputTokens: $response->usage->completionTokens,
            provider: $agent->provider(),
            model: $agent->model(),
            metadata: $response->meta->toArray(),
        );
    }

    /**
     * Execute the agent in structured output mode.
     *
     * Builds a StructuredAnonymousAgent with the JSON schema returned by the
     * agent's outputSchema() method, then passes the raw structured array
     * through mapOutput() before constructing the result.
     *
     * @param  AgentAction&HasStructuredOutput  $agent  The structured agent action.
     * @param  AgentContext  $context  The runtime context.
     * @return AgentResult The structured result.
     */
    private function executeStructured(
        AgentAction&HasStructuredOutput $agent,
        AgentContext $context,
    ): AgentResult {
        $schema = $agent->outputSchema();
        $tools = $agent instanceof HasTools ? $agent->tools() : [];

        $sdkAgent = new StructuredAnonymousAgent(
            instructions: $agent->instructions($context),
            messages: [],
            tools: $tools,
            schema: function (JsonSchema $jsonSchema) use ($schema): Type {
                return $this->buildSchema($jsonSchema, $schema);
            },
        );

        $response = $sdkAgent->prompt(
            prompt: $agent->prompt($context),
            provider: $agent->provider(),
            model: $agent->model(),
        );

        if (! $response instanceof StructuredAgentResponse) {
            throw new \UnexpectedValueException(
                sprintf('Expected StructuredAgentResponse, got %s.', $response::class)
            );
        }

        $raw = $response->toArray();
        $mapped = $agent->mapOutput($raw);

        return new AgentResult(
            text: $response->text,
            format: OutputFormat::Structured,
            structured: $mapped,
            inputTokens: $response->usage->promptTokens,
            outputTokens: $response->usage->completionTokens,
            provider: $agent->provider(),
            model: $agent->model(),
            metadata: $response->meta->toArray(),
        );
    }

    /**
     * Execute the agent in streaming mode.
     *
     * Iterates the streaming response, passing each TextDelta chunk to
     * onChunk(). If onChunk() returns false the stream is halted. Once the
     * stream is fully consumed onComplete() is called with a final AgentResult.
     *
     * @param  AgentAction&HasStreamingResponse  $agent  The streaming agent action.
     * @param  AgentContext  $context  The runtime context.
     * @return AgentResult The final result after stream completion.
     */
    private function executeStreaming(
        AgentAction&HasStreamingResponse $agent,
        AgentContext $context,
    ): AgentResult {
        $sdkAgent = $this->buildAnonymousAgent($agent, $context);

        $stream = $sdkAgent->stream(
            prompt: $agent->prompt($context),
            provider: $agent->provider(),
            model: $agent->model(),
        );

        foreach ($stream as $event) {
            if ($event instanceof TextDelta) {
                $shouldContinue = $agent->onChunk($event->delta);

                if (! $shouldContinue) {
                    break;
                }
            }
        }

        $fullText = $stream->text ?? '';
        $usage = $stream->usage;

        $result = new AgentResult(
            text: $fullText,
            format: OutputFormat::Text,
            structured: null,
            inputTokens: $usage->promptTokens ?? 0,
            outputTokens: $usage->completionTokens ?? 0,
            provider: $agent->provider(),
            model: $agent->model(),
            metadata: [],
        );

        $agent->onComplete($result);

        return $result;
    }

    /**
     * Build a plain AnonymousAgent with the correct instructions and optional tools.
     *
     * @param  AgentAction  $agent  The agent action providing instructions and optional tools.
     * @param  AgentContext  $context  The runtime context used to build the instructions string.
     * @return AnonymousAgent The configured anonymous agent instance.
     */
    private function buildAnonymousAgent(AgentAction $agent, AgentContext $context): AnonymousAgent
    {
        $tools = $agent instanceof HasTools ? $agent->tools() : [];

        return new AnonymousAgent(
            instructions: $agent->instructions($context),
            messages: [],
            tools: $tools,
        );
    }

    /**
     * Recursively convert a plain JSON Schema array into a JsonSchema Type object
     * tree that the Illuminate\JsonSchema serializer expects.
     *
     * The agent's outputSchema() returns a raw PHP array following the JSON Schema
     * spec. StructuredAnonymousAgent's schema closure must return a Type instance,
     * not a plain array, so we walk the array and map each node to the appropriate
     * JsonSchema factory method.
     *
     * @param  JsonSchema  $factory  The factory passed by StructuredAnonymousAgent.
     * @param  array<string, mixed>  $schema  The plain JSON Schema array from outputSchema().
     */
    private function buildSchema(JsonSchema $factory, array $schema): Type
    {
        $type = $schema['type'] ?? 'string';
        $required = (array) ($schema['required'] ?? []);

        return match ($type) {
            'object' => $this->buildObjectType($factory, $schema, $required),
            'array' => $this->buildArrayType($factory, $schema),
            'integer', 'number' => $type === 'integer' ? $factory->integer() : $factory->number(),
            'boolean' => $factory->boolean(),
            default => $this->buildStringType($factory, $schema),
        };
    }

    /**
     * Build an ObjectType from a JSON Schema object node.
     *
     * @param  array<string>  $required  List of required property keys.
     * @param  array<string, mixed>  $schema  The object schema array.
     */
    private function buildObjectType(JsonSchema $factory, array $schema, array $required): Type
    {
        $properties = [];

        foreach ($schema['properties'] ?? [] as $key => $propSchema) {
            $prop = $this->buildSchema($factory, $propSchema);

            if (in_array($key, $required, true)) {
                $prop->required();
            }

            $properties[$key] = $prop;
        }

        return $factory->object($properties);
    }

    /**
     * Build an ArrayType from a JSON Schema array node.
     *
     * @param  array<string, mixed>  $schema  The array schema node.
     */
    private function buildArrayType(JsonSchema $factory, array $schema): Type
    {
        $arrayType = $factory->array();

        if (isset($schema['items']) && is_array($schema['items'])) {
            $arrayType->items($this->buildSchema($factory, $schema['items']));
        }

        return $arrayType;
    }

    /**
     * Build a StringType, applying enum values when present.
     *
     * @param  array<string, mixed>  $schema  The string schema node.
     */
    private function buildStringType(JsonSchema $factory, array $schema): Type
    {
        $stringType = $factory->string();

        if (isset($schema['enum']) && is_array($schema['enum'])) {
            $stringType->enum($schema['enum']);
        }

        return $stringType;
    }
}
