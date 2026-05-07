<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;

describe('make:ai-action command', function (): void {
    beforeEach(function (): void {
        $this->files = app(Filesystem::class);
        $this->targetPath = app_path('Ai/Actions/TestAction.php');
        $this->mcpTargetPath = app_path('Ai/Actions/TestMcpAction.php');
    });

    afterEach(function (): void {
        $this->files->deleteDirectory(app_path('Ai'));
    });

    it('generates a new action file at the expected path', function (): void {
        $this->artisan('make:ai-action', ['name' => 'TestAction'])
            ->assertSuccessful();

        expect($this->files->exists($this->targetPath))->toBeTrue();
    });

    it('converts the name to StudlyCase', function (): void {
        $this->artisan('make:ai-action', ['name' => 'my_test_action'])
            ->assertSuccessful();

        $studlyPath = app_path('Ai/Actions/MyTestAction.php');
        expect($this->files->exists($studlyPath))->toBeTrue();
        $this->files->delete($studlyPath);
    });

    it('outputs a success message with the class name', function (): void {
        $this->artisan('make:ai-action', ['name' => 'TestAction'])
            ->expectsOutputToContain('TestAction')
            ->assertSuccessful();
    });

    it('fails when the file already exists', function (): void {
        $this->artisan('make:ai-action', ['name' => 'TestAction'])->assertSuccessful();

        $this->artisan('make:ai-action', ['name' => 'TestAction'])
            ->assertFailed();
    });

    it('generates an MCP-extended stub when --mcp flag is given', function (): void {
        $this->artisan('make:ai-action', ['name' => 'TestMcpAction', '--mcp' => true])
            ->assertSuccessful();

        expect($this->files->exists($this->mcpTargetPath))->toBeTrue();

        $contents = $this->files->get($this->mcpTargetPath);
        expect($contents)->toContain('ExposedAsMcpTool');
    });

    it('includes the mcp_name in the output when --mcp is used', function (): void {
        $this->artisan('make:ai-action', ['name' => 'TestMcpAction', '--mcp' => true])
            ->expectsOutputToContain('test_mcp_action')
            ->assertSuccessful();
    });

    it('places the correct namespace in the generated file', function (): void {
        $this->artisan('make:ai-action', ['name' => 'TestAction'])->assertSuccessful();

        $contents = $this->files->get($this->targetPath);
        expect($contents)->toContain('App\\Ai\\Actions');
    });

    it('fails with an error when the resolved stub file does not exist', function (): void {
        $realFiles = app(Filesystem::class);

        $mockFiles = Mockery::mock(Filesystem::class);
        $mockFiles->shouldReceive('exists')
            ->andReturnUsing(function (string $path) use ($realFiles): bool {
                if (str_ends_with($path, '.stub')) {
                    return false;
                }

                return $realFiles->exists($path);
            });

        app()->bind(Filesystem::class, fn () => $mockFiles);

        $this->artisan('make:ai-action', ['name' => 'StubMissingAction'])
            ->expectsOutputToContain('Stub file not found')
            ->assertFailed();

        app()->bind(Filesystem::class, fn () => app()->make(Filesystem::class));
    });

    it('uses the published stub when one exists in the project stubs/ directory', function (): void {
        $publishedStubDir = base_path('stubs');
        $publishedStubPath = $publishedStubDir.'/ai-action.stub';

        $packageStubPath = dirname(__DIR__, 2).'/stubs/ai-action.stub';
        $this->files->ensureDirectoryExists($publishedStubDir);
        $this->files->copy($packageStubPath, $publishedStubPath);

        try {
            $this->artisan('make:ai-action', ['name' => 'TestAction'])
                ->assertSuccessful();

            expect($this->files->exists($this->targetPath))->toBeTrue();
        } finally {
            $this->files->delete($publishedStubPath);
            if ($this->files->isDirectory($publishedStubDir) && count($this->files->files($publishedStubDir)) === 0) {
                $this->files->deleteDirectory($publishedStubDir);
            }
        }
    });
});
