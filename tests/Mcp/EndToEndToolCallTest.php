<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Pixelworxio\LaravelAiAction\Mcp\AgentActionMcpTool;
use Pixelworxio\LaravelAiAction\Mcp\Bridge;
use Pixelworxio\LaravelAiAction\Mcp\Facades\AiActionMcp;
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
describe('MCP Bridge end-to-end tool call', function (): void {
    beforeEach(function (): void {
        FakeAgentAction::reset();
        app(Bridge::class)->reset();
    });

    it('AiActionMcp::tool() stores a Registration and Bridge::flush() commits to Mcp facade', function (): void {
        FakeAgentAction::fakeResponse(StubMcpAction::class, 'flushed');

        AiActionMcp::tool(StubMcpAction::class);

        app(Bridge::class)->flush();

        $tools = app(Bridge::class)->tools();

        expect($tools)->toHaveCount(1);
        expect($tools[0])->toBeInstanceOf(AgentActionMcpTool::class);
        expect($tools[0]->name())->toBe('stub_mcp_action');
    });

    it('name override via Registration::name() is applied on commit', function (): void {
        FakeAgentAction::fakeResponse(StubMcpAction::class, 'ok');

        AiActionMcp::tool(StubMcpAction::class)->name('custom_tool_name');

        app(Bridge::class)->flush();

        $tool = app(Bridge::class)->tools()[0];

        expect($tool->name())->toBe('custom_tool_name');
    });

    it('duplicate registrations are deduplicated (last explicit wins)', function (): void {
        FakeAgentAction::fakeResponse(StubMcpAction::class, 'ok');

        AiActionMcp::tool(StubMcpAction::class)->name('first');
        AiActionMcp::tool(StubMcpAction::class)->name('second');

        app(Bridge::class)->flush();

        $tools = app(Bridge::class)->tools();

        expect($tools)->toHaveCount(1);
        expect($tools[0]->name())->toBe('second');
    });

    it('a registered tool produces a Response when handle() is called', function (): void {
        FakeAgentAction::fakeResponse(StubMcpAction::class, 'the answer');

        AiActionMcp::tool(StubMcpAction::class);
        app(Bridge::class)->flush();

        $tool = app(Bridge::class)->tools()[0];
        $request = Request::create('/', 'POST', ['prompt' => 'question']);

        $response = $tool->handle($request);

        expect($response)->toBeInstanceOf(Response::class);
        expect((string) $response->content())->toBe('the answer');
    });

    it('auto-discovery picks up #[ExposesAsMcpTool] classes from discover_in paths', function (): void {
        FakeAgentAction::fakeResponse(StubMcpAction::class, 'discovered');
        FakeAgentAction::fakeResponse(StubReadOnlyMcpAction::class, 'also discovered');

        config(['ai-action.mcp.discover_in' => [
            __DIR__.'/../Fixtures/Mcp/Actions',
        ]]);

        app(Bridge::class)->flush();

        $names = array_map(fn ($t) => $t->name(), app(Bridge::class)->tools());

        expect($names)->toContain('stub_mcp_action');
        expect($names)->toContain('stub_readonly_action');
    });

    it('explicit registrations are not overridden by auto-discovery', function (): void {
        FakeAgentAction::fakeResponse(StubMcpAction::class, 'ok');

        AiActionMcp::tool(StubMcpAction::class)->name('explicit_name');

        config(['ai-action.mcp.discover_in' => [
            __DIR__.'/../Fixtures/Mcp/Actions',
        ]]);

        app(Bridge::class)->flush();

        $names = array_map(fn ($t) => $t->name(), app(Bridge::class)->tools());

        // The explicit name must be present; the auto-discovered default must not replace it.
        expect($names)->toContain('explicit_name');
        expect($names)->not->toContain('stub_mcp_action');
    });
});
