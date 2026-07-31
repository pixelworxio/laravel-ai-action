<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Actions;

use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\StructuredAnonymousAgent;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Contracts\AgentActionMiddleware;
use Pixelworxio\LaravelAiAction\Contracts\HasMiddleware;
use Pixelworxio\LaravelAiAction\Contracts\HasStreamingResponse;
use Pixelworxio\LaravelAiAction\Contracts\HasStructuredOutput;
use Pixelworxio\LaravelAiAction\Contracts\HasTimeout;
use Pixelworxio\LaravelAiAction\Contracts\HasTools;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Enums\OutputFormat;
use Pixelworxio\LaravelAiAction\Events\AgentActionCompleted;
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
        $startedAt = hrtime(true);

        $destination = fn (AgentAction $agent, AgentContext $context): AgentResult => $this->runOnce($agent, $context);

        $result = $agent instanceof HasMiddleware
            ? $this->through($agent->middleware(), $destination)($agent, $context)
            : $destination($agent, $context);

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;

        if ((bool) config('ai-action.logging', false)) {
            Log::info('ai-action.executed', [
                'agent' => $agent::class,
                'provider' => $result->provider,
                'model' => $result->model,
                'input_tokens' => $result->inputTokens,
                'output_tokens' => $result->outputTokens,
            ]);
        }

        Event::dispatch(new AgentActionCompleted(
            agentClass: $agent::class,
            result: $result,
            durationMs: $durationMs,
        ));

        return $result;
    }

    /**
     * Run the agent exactly once, with no middleware involved.
     *
     * This is the innermost "destination" that a middleware pipeline (when
     * the agent implements HasMiddleware) ultimately wraps. Selects the
     * correct execution branch and normalises any failure into an
     * AgentException, exactly as execute() did before middleware support
     * was introduced.
     *
     * @param  AgentAction  $agent  The agent action to execute.
     * @param  AgentContext  $context  The runtime context for the invocation.
     * @return AgentResult The typed result wrapping the AI response.
     *
     * @throws AgentException When the AI provider call fails.
     */
    private function runOnce(AgentAction $agent, AgentContext $context): AgentResult
    {
        try {
            return match (true) {
                $agent instanceof HasStructuredOutput => $this->executeStructured($agent, $context),
                $agent instanceof HasStreamingResponse => $this->executeStreaming($agent, $context),
                default => $this->executeText($agent, $context),
            };
        } catch (AgentException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw AgentException::fromThrowable($agent, $e);
        }
    }

    /**
     * Compose a middleware pipeline around the given destination closure.
     *
     * The first entry in $middleware is outermost, matching Laravel's queued
     * job middleware convention.
     *
     * @param  array<int, AgentActionMiddleware>  $middleware
     * @param  Closure(AgentAction, AgentContext): AgentResult  $destination
     * @return Closure(AgentAction, AgentContext): AgentResult
     */
    private function through(array $middleware, Closure $destination): Closure
    {
        return array_reduce(
            array_reverse($middleware),
            static fn (Closure $next, AgentActionMiddleware $stage): Closure => static fn (AgentAction $agent, AgentContext $context): AgentResult => $stage->handle($agent, $context, $next),
            $destination,
        );
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
            provider: $this->providerFor($agent, $context),
            model: $this->modelFor($agent, $context),
            timeout: $this->timeoutFor($agent),
        );

        return new AgentResult(
            text: $response->text,
            format: OutputFormat::Text,
            structured: null,
            inputTokens: $response->usage->promptTokens,
            outputTokens: $response->usage->completionTokens,
            provider: $this->providerFor($agent, $context),
            model: $this->modelFor($agent, $context),
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
            schema: function (JsonSchema $jsonSchema) use ($schema): array {
                return $this->buildSchemaProperties($jsonSchema, $schema);
            },
        );

        $response = $sdkAgent->prompt(
            prompt: $agent->prompt($context),
            provider: $this->providerFor($agent, $context),
            model: $this->modelFor($agent, $context),
            timeout: $this->timeoutFor($agent),
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
            provider: $this->providerFor($agent, $context),
            model: $this->modelFor($agent, $context),
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
            provider: $this->providerFor($agent, $context),
            model: $this->modelFor($agent, $context),
            timeout: $this->timeoutFor($agent),
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
        $usage = $stream->usage ?? new Usage;

        $result = new AgentResult(
            text: $fullText,
            format: OutputFormat::Text,
            structured: null,
            inputTokens: $usage->promptTokens,
            outputTokens: $usage->completionTokens,
            provider: $this->providerFor($agent, $context),
            model: $this->modelFor($agent, $context),
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

    private function timeoutFor(AgentAction $agent): ?int
    {
        return $agent instanceof HasTimeout ? $agent->timeout() : null;
    }

    /**
     * Resolve the provider to use, preferring a context override (set by
     * middleware such as FallbackProvider) over the agent's own provider().
     */
    private function providerFor(AgentAction $agent, AgentContext $context): string
    {
        return $context->providerOverride ?? $agent->provider();
    }

    /**
     * Resolve the model to use, preferring a context override (set by
     * middleware such as FallbackProvider) over the agent's own model().
     */
    private function modelFor(AgentAction $agent, AgentContext $context): string
    {
        return $context->modelOverride ?? $agent->model();
    }

    /**
     * Return the top-level properties array that StructuredAnonymousAgent::schema() must return.
     *
     * The SDK's HasStructuredOutput::schema() contract returns array<string, Type> — a flat
     * map of property name to Type object — which the SDK then wraps in an ObjectSchema
     * internally. This method extracts and builds that map from the agent's outputSchema()
     * array, handling the required flags for each top-level property.
     *
     * @param  JsonSchema  $factory  The factory passed by StructuredAnonymousAgent.
     * @param  array<string, mixed>  $schema  The plain JSON Schema array from outputSchema().
     * @return array<string, Type>
     */
    private function buildSchemaProperties(JsonSchema $factory, array $schema): array
    {
        $required = (array) ($schema['required'] ?? []);
        $properties = [];

        foreach ($schema['properties'] ?? [] as $key => $propSchema) {
            $prop = $this->buildSchema($factory, $propSchema);

            if (in_array($key, $required, true)) {
                $prop->required();
            }

            $properties[$key] = $prop;
        }

        return $properties;
    }

    /**
     * Recursively convert a plain JSON Schema node into a JsonSchema Type object.
     *
     * Used for nested schema nodes (object properties, array items, etc.).
     * The top-level call is handled by buildSchemaProperties(), which returns
     * the flat array<string, Type> that the SDK's schema() contract requires.
     *
     * @param  JsonSchema  $factory  The factory passed by StructuredAnonymousAgent.
     * @param  array<string, mixed>  $schema  A single JSON Schema node from outputSchema().
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
