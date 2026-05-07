<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Tool;
use Pixelworxio\LaravelAiAction\Mcp\Discovery\AttributeScanner;
use Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions\NonQualifying\AbstractAction;
use Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions\NonQualifying\NotAnAction;
use Pixelworxio\LaravelAiAction\Tests\Fixtures\Mcp\Actions\NonQualifying\NotExposed;
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

    it('skips PHP files that have no class definition', function (): void {
        config(['ai-action.mcp.cache_discovery' => false]);

        $nonQualifyingPath = $this->fixturesPath.'/NonQualifying';

        // The no_class.php file is in this directory; no exception should be raised.
        $discovered = $this->scanner->scan([$nonQualifyingPath]);

        $classNames = array_map(fn ($class) => basename(str_replace('\\', '/', $class)), $discovered);
        expect($classNames)->not->toContain('');
    });

    it('does not discover classes that only implement AgentAction (not ExposedAsMcpTool)', function (): void {
        config(['ai-action.mcp.cache_discovery' => false]);

        $nonQualifyingPath = $this->fixturesPath.'/NonQualifying';

        // Ensure fixture classes are autoloaded for reflection
        class_exists(NotExposed::class);

        $discovered = $this->scanner->scan([$nonQualifyingPath]);

        expect($discovered)->not->toContain(
            NotExposed::class
        );
    });

    it('does not discover classes that only implement ExposedAsMcpTool (not AgentAction)', function (): void {
        config(['ai-action.mcp.cache_discovery' => false]);

        $nonQualifyingPath = $this->fixturesPath.'/NonQualifying';

        class_exists(NotAnAction::class);

        $discovered = $this->scanner->scan([$nonQualifyingPath]);

        expect($discovered)->not->toContain(
            NotAnAction::class
        );
    });

    it('does not discover abstract classes carrying the attribute', function (): void {
        config(['ai-action.mcp.cache_discovery' => false]);

        $nonQualifyingPath = $this->fixturesPath.'/NonQualifying';

        class_exists(AbstractAction::class);

        $discovered = $this->scanner->scan([$nonQualifyingPath]);

        expect($discovered)->not->toContain(
            AbstractAction::class
        );
    });

    it('returns null and skips files that cannot be read (file_get_contents returns false)', function (): void {
        config(['ai-action.mcp.cache_discovery' => false]);

        $tmpDir = sys_get_temp_dir().'/scanner_test_'.uniqid();
        mkdir($tmpDir, 0755);

        $unreadable = $tmpDir.'/unreadable.php';
        file_put_contents($unreadable, '<?php class UnreadableFixtureClass {}');
        chmod($unreadable, 0000);

        try {
            $discovered = $this->scanner->scan([$tmpDir]);
            expect($discovered)->not->toContain('UnreadableFixtureClass');
        } finally {
            chmod($unreadable, 0644);
            unlink($unreadable);
            rmdir($tmpDir);
        }
    });

    it('silently skips PHP files whose class cannot be autoloaded', function (): void {
        config(['ai-action.mcp.cache_discovery' => false]);

        $nonQualifyingPath = $this->fixturesPath.'/NonQualifying';

        // UnloadableClass.php declares a class under Some\Totally\Unloadable\Namespace which
        // is not in the PSR-4 map, so class_exists() returns false inside qualifies().
        $discovered = $this->scanner->scan([$nonQualifyingPath]);

        expect($discovered)->not->toContain('Some\\Totally\\Unloadable\\Namespace\\UnloadableClass');
    });
});
