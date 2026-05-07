<?php

declare(strict_types=1);

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Contracts\Transport;
use Laravel\Mcp\Server\Tool;
use Pixelworxio\LaravelAiAction\Mcp\AgentActionMcpTool;
use Pixelworxio\LaravelAiAction\Mcp\AgentActionServer;
use Pixelworxio\LaravelAiAction\Mcp\Bridge;
use Pixelworxio\LaravelAiAction\Testing\FakeAgentAction;
use Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions\StubMcpAction;
use Pixelworxio\LaravelAiAction\Tests\McpTestCase;

uses(McpTestCase::class)->group('mcp');

if (! class_exists(Tool::class)) {
    require_once __DIR__.'/../Fixtures/Mcp/bootstrap.php';
}

// A minimal concrete subclass that exposes boot() and the tools array.
class TestAgentActionServer extends AgentActionServer
{
    public function triggerBoot(): void
    {
        $this->boot();
    }

    public function getTools(): array
    {
        return $this->tools;
    }
}

function makeTestServer(): TestAgentActionServer
{
    $transport = Mockery::mock(Transport::class);

    return new TestAgentActionServer($transport);
}

describe('AgentActionServer', function (): void {
    beforeEach(function (): void {
        FakeAgentAction::reset();
        app(Bridge::class)->reset();
    });

    it('extends Laravel\Mcp\Server', function (): void {
        expect(TestAgentActionServer::class)->toExtend(Server::class);
    });

    it('boot() pulls tools from Bridge into $tools', function (): void {
        FakeAgentAction::fakeResponse(StubMcpAction::class, 'ok');
        app(Bridge::class)->tool(StubMcpAction::class);
        app(Bridge::class)->flush();

        $server = makeTestServer();
        $server->triggerBoot();

        expect($server->getTools())->not->toBeEmpty()
            ->and($server->getTools()[0])->toBeInstanceOf(AgentActionMcpTool::class);
    });

    it('boot() results in empty tools when Bridge has no registrations', function (): void {
        $server = makeTestServer();
        $server->triggerBoot();

        expect($server->getTools())->toBeEmpty();
    });
});
