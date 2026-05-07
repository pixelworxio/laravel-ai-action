<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Mcp\Discovery;

use Illuminate\Support\Facades\Cache;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\Mcp\Attributes\ExposesAsMcpTool;
use Pixelworxio\LaravelAiAction\Mcp\Contracts\ExposedAsMcpTool as ExposedAsMcpToolContract;
use Symfony\Component\Finder\Finder;

/**
 * Scans configured directories for classes carrying the #[ExposesAsMcpTool] attribute.
 *
 * Results are cached (keyed on the path list) when ai-action.mcp.cache_discovery
 * is true. The cache is invalidated on composer dump-autoload via the classmap
 * checksum mechanism. In local development you can set
 * AI_ACTION_MCP_CACHE_DISCOVERY=false to always scan fresh.
 *
 * Only concrete classes implementing both AgentAction AND ExposedAsMcpTool are
 * returned. Abstract classes, interfaces, and traits are silently skipped.
 */
final class AttributeScanner
{
    private const CACHE_KEY_PREFIX = 'ai-action.mcp.discovered:';

    /**
     * Scan the given directories and return discovered action class names.
     *
     * @param  list<string>  $paths  Absolute directory paths to scan.
     * @return list<class-string<AgentAction&ExposedAsMcpToolContract>>
     */
    public function scan(array $paths): array
    {
        if ($paths === []) {
            return [];
        }

        if ((bool) config('ai-action.mcp.cache_discovery', true)) {
            return Cache::rememberForever(
                key: self::CACHE_KEY_PREFIX.md5(implode('|', $paths)),
                callback: fn (): array => $this->discover($paths),
            );
        }

        return $this->discover($paths);
    }

    /**
     * Invalidate the discovery cache for the given paths.
     *
     * @param  list<string>  $paths
     */
    public function invalidate(array $paths): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX.md5(implode('|', $paths)));
    }

    /**
     * Perform the filesystem scan and return qualifying class names.
     *
     * @param  list<string>  $paths
     * @return list<class-string<AgentAction&ExposedAsMcpToolContract>>
     */
    private function discover(array $paths): array
    {
        $existingPaths = array_filter($paths, 'is_dir');

        if ($existingPaths === []) {
            return [];
        }

        $finder = Finder::create()
            ->files()
            ->name('*.php')
            ->in($existingPaths);

        $classes = [];

        foreach ($finder as $file) {
            $class = $this->classFromFile($file->getRealPath());

            if ($class === null) {
                continue;
            }

            if (! $this->qualifies($class)) {
                continue;
            }

            /** @var class-string<AgentAction&ExposedAsMcpToolContract> $class */
            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * Extract the fully-qualified class name from a PHP file via token parsing.
     *
     * Uses PHP's tokenizer rather than including the file, so no side effects.
     *
     * @param  string  $path  Absolute path to the PHP file.
     * @return class-string|null The FQCN, or null if none could be determined.
     */
    private function classFromFile(string $path): ?string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $tokens = token_get_all($contents);
        $namespace = '';
        $className = null;
        $i = 0;
        $count = count($tokens);

        while ($i < $count) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $i++;
                $ns = '';

                while ($i < $count) {
                    $t = $tokens[$i];

                    if (is_array($t) && in_array($t[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                        $ns .= $t[1];
                    } elseif ($t === ';' || $t === '{') {
                        break;
                    }

                    $i++;
                }

                $namespace = $ns;
            }

            if (is_array($token) && $token[0] === T_CLASS) {
                $i++;

                while ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
                    $i++;
                }

                if ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                    $className = $tokens[$i][1];
                    break;
                }
            }

            $i++;
        }

        if ($className === null) {
            return null;
        }

        /** @var class-string $fqcn */
        $fqcn = $namespace !== '' ? $namespace.'\\'.$className : $className;

        return $fqcn;
    }

    /**
     * Determine whether a class qualifies for auto-registration.
     *
     * A qualifying class must:
     *  1. Be loadable (autoloadable at the time of scanning).
     *  2. Be a concrete class (not abstract, interface, or trait).
     *  3. Implement both AgentAction and ExposedAsMcpTool.
     *  4. Carry the #[ExposesAsMcpTool] PHP attribute.
     *
     * @param  class-string  $class
     */
    private function qualifies(string $class): bool
    {
        if (! class_exists($class)) {
            return false;
        }

        $reflection = new \ReflectionClass($class);

        if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
            return false;
        }

        if (! $reflection->implementsInterface(AgentAction::class)) {
            return false;
        }

        if (! $reflection->implementsInterface(ExposedAsMcpToolContract::class)) {
            return false;
        }

        return $reflection->getAttributes(ExposesAsMcpTool::class) !== [];
    }
}
