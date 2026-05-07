<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Tool;
use Pixelworxio\LaravelAiAction\Mcp\Discovery\AttributeScanner;
use Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions\StubMcpAction;
use Pixelworxio\LaravelAiAction\Tests\McpTestCase;

uses(McpTestCase::class)->group('mcp');

if (! class_exists(Tool::class)) {
    require_once __DIR__.'/../Fixtures/Mcp/bootstrap.php';
}

/**
 * @group mcp
 */
describe('AttributeScanner', function (): void {
    beforeEach(function (): void {
        // Ensure the fixture action class is loaded for reflection.
        class_exists(StubMcpAction::class);

        $this->scanner = new AttributeScanner;
        $this->fixturesPath = __DIR__.'/../Fixtures/Mcp/Actions';
    });

    it('discovers classes carrying #[ExposesAsMcpTool] in the given path', function (): void {
        config(['ai-action.mcp.cache_discovery' => false]);

        $discovered = $this->scanner->scan([$this->fixturesPath]);

        expect($discovered)->toContain(StubMcpAction::class);
    });

    it('returns an empty array for an empty path list', function (): void {
        $discovered = $this->scanner->scan([]);

        expect($discovered)->toBeEmpty();
    });

    it('silently ignores paths that do not exist', function (): void {
        config(['ai-action.mcp.cache_discovery' => false]);

        $discovered = $this->scanner->scan(['/totally/fake/path']);

        expect($discovered)->toBeEmpty();
    });

    it('does not discover abstract classes', function (): void {
        config(['ai-action.mcp.cache_discovery' => false]);

        $discovered = $this->scanner->scan([$this->fixturesPath]);

        foreach ($discovered as $class) {
            $reflection = new ReflectionClass($class);
            expect($reflection->isAbstract())->toBeFalse("Abstract class [{$class}] should not be discovered.");
        }
    });

    it('caches results when cache_discovery is true', function (): void {
        config(['ai-action.mcp.cache_discovery' => true]);

        $first = $this->scanner->scan([$this->fixturesPath]);

        // Invalidate then re-scan; second call should re-populate cache.
        $this->scanner->invalidate([$this->fixturesPath]);
        $second = $this->scanner->scan([$this->fixturesPath]);

        expect($first)->toEqual($second);
    });
});
