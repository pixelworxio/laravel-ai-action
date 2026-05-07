<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Mcp\AgentActionMcpTool;
use Pixelworxio\LaravelAiAction\Mcp\AgentResultResponder;
use Pixelworxio\LaravelAiAction\Mcp\Registration;
use Pixelworxio\LaravelAiAction\Testing\FakeAgentAction;
use Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions\StubMcpAction;
use Pixelworxio\LaravelAiAction\Tests\McpTestCase;

uses(McpTestCase::class)->group('mcp');

if (! class_exists(Tool::class)) {
    require_once __DIR__.'/../Fixtures/Mcp/bootstrap.php';
}

describe('Registration', function (): void {
    beforeEach(function (): void {
        FakeAgentAction::reset();
    });

    it('build() creates an AgentActionMcpTool with the action class', function (): void {
        $registration = new Registration(StubMcpAction::class);

        $tool = $registration->build();

        expect($tool)->toBeInstanceOf(AgentActionMcpTool::class)
            ->and($tool->name())->toBe('stub_mcp_action');
    });

    it('name() overrides the tool name advertised to MCP clients', function (): void {
        $registration = new Registration(StubMcpAction::class);
        $result = $registration->name('custom_name');

        expect($result)->toBe($registration);

        $tool = $registration->build();

        expect($tool->name())->toBe('custom_name');
    });

    it('responder() overrides the AgentResultResponder for this tool', function (): void {
        FakeAgentAction::fakeResponse(StubMcpAction::class, 'ok');

        $customResponder = new class extends AgentResultResponder
        {
            public function respond(AgentResult $result, AgentAction $action): Response|array
            {
                return Response::text('custom_responder_was_used');
            }
        };

        $registration = new Registration(StubMcpAction::class);
        $result = $registration->responder($customResponder);

        expect($result)->toBe($registration);

        $tool = $registration->build();
        $request = Request::create('/', 'POST', ['prompt' => 'test']);
        $response = $tool->handle($request);

        expect((string) $response->content())->toBe('custom_responder_was_used');
    });
});
