<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Artisan command that generates a new AI agent action class from a stub.
 *
 * Usage:
 *   php artisan make:ai-action MyAction
 *   php artisan make:ai-action MyAction --mcp
 *
 * The generated file is placed at app/Ai/Actions/{Name}.php. The base stub
 * implements AgentAction with the InteractsWithAgent trait for sensible defaults.
 * The --mcp flag emits an extended stub that also implements ExposedAsMcpTool
 * and includes the BridgesAgentContextToMcp trait.
 */
final class MakeAiActionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:ai-action
        {name : The name of the action class to generate}
        {--mcp : Also implement ExposedAsMcpTool for MCP bridge support}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new AI action class';

    /**
     * Execute the console command.
     *
     * Reads the appropriate stub (base or MCP-extended), replaces placeholders,
     * and writes the generated class to app/Ai/Actions/{Name}.php.
     *
     * @param  Filesystem  $files  The filesystem instance for reading/writing files.
     * @return int The command exit code (0 = success, 1 = failure).
     */
    public function handle(Filesystem $files): int
    {
        $name = $this->argument('name');

        if (! is_string($name)) {
            $this->components->error('The name argument must be a string.');

            return self::FAILURE;
        }

        $className = Str::studly($name);
        $targetPath = app_path('Ai/Actions/'.$className.'.php');

        if ($files->exists($targetPath)) {
            $this->components->error("Action [{$className}] already exists.");

            return self::FAILURE;
        }

        $withMcp = (bool) $this->option('mcp');
        $stubPath = $this->resolveStubPath($withMcp);

        if (! $files->exists($stubPath)) {
            $this->components->error("Stub file not found at [{$stubPath}].");

            return self::FAILURE;
        }

        $stub = $files->get($stubPath);
        $namespace = $this->rootNamespace().'Ai\\Actions';
        $mcpName = Str::snake($className);

        $contents = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ mcp_name }}'],
            [$namespace, $className, $mcpName],
            $stub,
        );

        $directory = dirname($targetPath);

        if (! $files->isDirectory($directory)) {
            $files->makeDirectory($directory, 0755, recursive: true);
        }

        $files->put($targetPath, $contents);

        $this->components->info("Action [{$className}] created successfully.");
        $this->components->twoColumnDetail('Path', $targetPath);

        if ($withMcp) {
            $this->components->twoColumnDetail('MCP tool name', $mcpName);
        }

        return self::SUCCESS;
    }

    /**
     * Resolve the path to the stub file.
     *
     * Resolution order:
     *  1. Published stub in the project root (stubs/ai-action[-mcp].stub).
     *  2. Package bundled stub.
     *
     * @param  bool  $mcp  Whether the MCP-extended stub is requested.
     * @return string The absolute path to the stub file.
     */
    private function resolveStubPath(bool $mcp): string
    {
        $stubName = $mcp ? 'ai-action-mcp.stub' : 'ai-action.stub';
        $published = base_path("stubs/{$stubName}");

        if (file_exists($published)) {
            return $published;
        }

        return dirname(__DIR__, 2)."/stubs/{$stubName}";
    }

    /**
     * Return the root namespace for the application.
     *
     * @return string The root namespace ending with a backslash.
     */
    private function rootNamespace(): string
    {
        return $this->laravel->getNamespace();
    }
}
