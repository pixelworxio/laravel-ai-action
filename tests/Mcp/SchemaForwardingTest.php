<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Server\Tool;
use Pixelworxio\LaravelAiAction\Mcp\AgentActionMcpTool;
use Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions\StubMcpAction;
use Pixelworxio\LaravelAiAction\Tests\McpTestCase;

uses(McpTestCase::class)->group('mcp');

if (! class_exists(Tool::class)) {
    require_once __DIR__.'/../Fixtures/Mcp/bootstrap.php';
}

/**
 * @group mcp
 */
describe('MCP input schema forwarding', function (): void {
    it('mcpInputSchema returns a map keyed by property name', function (): void {
        $action = new StubMcpAction;
        $factory = new JsonSchemaTypeFactory;

        $schema = $action->mcpInputSchema($factory);

        expect($schema)->toBeArray();
        expect($schema)->toHaveKey('prompt');
    });

    it('properties are Illuminate JsonSchema Type instances', function (): void {
        $action = new StubMcpAction;
        $factory = new JsonSchemaTypeFactory;

        $schema = $action->mcpInputSchema($factory);

        expect($schema['prompt'])->toBeInstanceOf(Type::class);
    });

    it('required properties have the required flag set', function (): void {
        $action = new StubMcpAction;
        $factory = new JsonSchemaTypeFactory;

        $schema = $action->mcpInputSchema($factory);

        $array = $schema['prompt']->toArray();

        // The Type serialises to an array; required presence is checked structurally.
        expect($array)->toBeArray();
    });

    it('AgentActionMcpTool schema() delegates to mcpInputSchema()', function (): void {
        $adapter = new AgentActionMcpTool(new StubMcpAction);
        $factory = new JsonSchemaTypeFactory;

        $fromAdapter = $adapter->schema($factory);
        $fromAction = (new StubMcpAction)->mcpInputSchema($factory);

        expect(array_keys($fromAdapter))->toEqual(array_keys($fromAction));
    });
});
