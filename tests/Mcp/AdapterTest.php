<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Exceptions\InvalidContextException;
use Pixelworxio\LaravelAiAction\Mcp\AgentActionMcpTool;
use Pixelworxio\LaravelAiAction\Mcp\AgentResultResponder;
use Pixelworxio\LaravelAiAction\Testing\FakeAgentAction;
use Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions\StubMcpAction;
use Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions\StubReadOnlyMcpAction;
use Pixelworxio\LaravelAiAction\Tests\McpTestCase;

uses(McpTestCase::class)->group('mcp');

if (! class_exists(Tool::class)) {
    require_once __DIR__.'/../Fixtures/Mcp/bootstrap.php';
}

/**
 * @group mcp
 */
describe('AgentActionMcpTool', function (): void {
    beforeEach(function (): void {
        FakeAgentAction::reset();
    });

    it('returns the action mcpName as tool name', function (): void {
        $adapter = new AgentActionMcpTool(new StubMcpAction);

        expect($adapter->name())->toBe('stub_mcp_action');
    });

    it('honours a name override set via withName()', function (): void {
        $adapter = new AgentActionMcpTool(new StubMcpAction);
        $adapter->withName('overridden_name');

        expect($adapter->name())->toBe('overridden_name');
    });

    it('returns the action mcpDescription', function (): void {
        $adapter = new AgentActionMcpTool(new StubMcpAction);

        expect($adapter->description())->toBe('A stub action for testing the MCP bridge.');
    });

    it('delegates schema() to the action mcpInputSchema()', function (): void {
        $adapter = new AgentActionMcpTool(new StubMcpAction);
        $factory = app(JsonSchema::class);

        $schema = $adapter->schema($factory);

        expect($schema)->toBeArray()->toHaveKey('prompt');
    });

    it('handle() resolves context and returns a Response on success', function (): void {
        FakeAgentAction::fakeResponse(StubMcpAction::class, 'fake text');

        $adapter = new AgentActionMcpTool(new StubMcpAction);
        $request = Request::create('/', 'POST', ['prompt' => 'hello']);

        $response = $adapter->handle($request);

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->isError())->toBeFalse();
        expect((string) $response->content())->toBe('fake text');
    });

    it('handle() returns Response::error when resolveContext throws InvalidContextException', function (): void {
        $action = new class extends StubMcpAction
        {
            public function resolveContext(array $input, ?Authenticatable $user): AgentContext
            {
                throw new InvalidContextException(
                    context: new AgentContext(
                        record: null, records: [], meta: [], userInstruction: null, panelId: null, resourceClass: null,
                    ),
                    message: 'bad input',
                );
            }
        };

        $adapter = new AgentActionMcpTool($action);
        $request = Request::create('/', 'POST', []);

        $response = $adapter->handle($request);

        expect($response->isError())->toBeTrue();
        expect((string) $response->content())->toBe('bad input');
    });

    it('forwards IsReadOnly annotation from the action class as key-value hint', function (): void {
        $adapter = new AgentActionMcpTool(new StubReadOnlyMcpAction);

        $annotations = $adapter->annotations();

        expect($annotations)->not->toBeEmpty();
        expect($annotations)->toHaveKey('readOnlyHint');
        expect($annotations['readOnlyHint'])->toBeTrue();
    });

    it('returns empty annotations when action carries none', function (): void {
        $adapter = new AgentActionMcpTool(new StubMcpAction);

        expect($adapter->annotations())->toBeEmpty();
    });

    it('accepts a custom responder via constructor', function (): void {
        FakeAgentAction::fakeResponse(StubMcpAction::class, 'from fake');

        $customResponder = new class extends AgentResultResponder
        {
            public function respond(AgentResult $result, AgentAction $action): Response|array
            {
                return Response::text('custom_responder');
            }
        };

        $adapter = new AgentActionMcpTool(new StubMcpAction, $customResponder);
        $request = Request::create('/', 'POST', ['prompt' => 'test']);

        $response = $adapter->handle($request);

        expect((string) $response->content())->toBe('custom_responder');
    });
});
