<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Artisan command that generates a new AI agent action class from the agent stub.
 *
 * Usage:
 *   php artisan make:agent MyAgent
 *
 * The generated file is placed at app/Ai/Agents/{Name}.php and implements
 * AgentAction using the InteractsWithAgent trait for sensible defaults.
 */
final class MakeAgentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:agent {name : The name of the agent class to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new AI agent action class';

    /**
     * Execute the console command.
     *
     * Reads the agent stub, replaces placeholders, and writes the generated
     * class to app/Ai/Agents/{Name}.php. Reports success or failure to the
     * console output.
     *
     * @param Filesystem $files The filesystem instance for reading/writing files.
     * @return int The command exit code (0 = success, 1 = failure).
     */
    public function handle(Filesystem $files): int
    {
        $name = (string) $this->argument('name');
        $className = Str::studly($name);
        $targetPath = app_path('Ai/Agents/' . $className . '.php');

        if ($files->exists($targetPath)) {
            $this->components->error("Agent [{$className}] already exists.");

            return self::FAILURE;
        }

        $stubPath = $this->resolveStubPath();

        if (! $files->exists($stubPath)) {
            $this->components->error("Stub file not found at [{$stubPath}].");

            return self::FAILURE;
        }

        $stub = $files->get($stubPath);
        $namespace = $this->rootNamespace() . 'Ai\\Agents';

        $contents = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $className],
            $stub,
        );

        $directory = dirname($targetPath);

        if (! $files->isDirectory($directory)) {
            $files->makeDirectory($directory, 0755, recursive: true);
        }

        $files->put($targetPath, $contents);

        $this->components->info("Agent [{$className}] created successfully.");
        $this->components->twoColumnDetail('Path', $targetPath);

        return self::SUCCESS;
    }

    /**
     * Resolve the path to the agent stub file.
     *
     * The stub is resolved from a published stub in the project root first,
     * falling back to the package's bundled stub.
     *
     * @return string The absolute path to the stub file.
     */
    private function resolveStubPath(): string
    {
        $published = base_path('stubs/agent.stub');

        if (file_exists($published)) {
            return $published;
        }

        return dirname(__DIR__, 2) . '/stubs/agent.stub';
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
