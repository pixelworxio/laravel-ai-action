<?php

declare(strict_types=1);

/**
 * MCP stub bootstrap.
 *
 * Defines minimal stub classes for the Laravel MCP package so that MCP bridge
 * tests can run in environments where laravel/mcp is not installed. Each stub
 * mirrors the public interface used by the bridge and test assertions.
 *
 * This file is required by McpTestCase::setUp() when class_exists(\Laravel\Mcp\Server\Tool::class)
 * returns false. When the real package is installed the stubs are never loaded.
 */

namespace Laravel\Mcp\Server\Contracts {

    interface Content extends \Stringable
    {
        /** @return array<string, mixed> */
        public function toArray(): array;

        /** @param array<string, mixed>|string $meta */
        public function setMeta(array|string $meta, mixed $value = null): void;
    }
}

namespace Laravel\Mcp\Server\Content {

    use Laravel\Mcp\Server\Contracts\Content;

    final class Text implements Content
    {
        /** @var array<string, mixed> */
        private array $meta = [];

        public function __construct(private string $text) {}

        /** @return array<string, mixed> */
        public function toArray(): array
        {
            $base = ['type' => 'text', 'text' => $this->text];

            return $this->meta !== [] ? [...$base, '_meta' => $this->meta] : $base;
        }

        public function setMeta(array|string $meta, mixed $value = null): void
        {
            if (! is_array($meta)) {
                $this->meta[$meta] = $value;

                return;
            }

            $this->meta = array_merge($this->meta, $meta);
        }

        public function __toString(): string
        {
            return $this->text;
        }
    }
}

namespace Laravel\Mcp {

    use Laravel\Mcp\Server\Content\Text;
    use Laravel\Mcp\Server\Contracts\Content;

    /**
     * Stub for Laravel\Mcp\Server — mirrors the surface used by AgentActionServer.
     *
     * AgentActionServer extends this class, accesses $this->tools[], and calls
     * parent::boot(). That is the entire surface the stub needs to cover.
     */
    abstract class Server
    {
        /** @var list<\Laravel\Mcp\Server\Tool> */
        protected array $tools = [];

        protected function boot(): void {}
    }

    /**
     * Stub for Laravel\Mcp\Response — mirrors the real public API.
     */
    final class Response
    {
        private function __construct(
            private Content $contentObj,
            private bool $isErrorFlag = false,
        ) {}

        public static function text(string $text): static
        {
            return new self(new Text($text));
        }

        public static function error(string $text): static
        {
            return new static(new Text($text), true);
        }

        public function content(): Content
        {
            return $this->contentObj;
        }

        public function isError(): bool
        {
            return $this->isErrorFlag;
        }

        /** @param array<string, mixed>|string $meta */
        public function withMeta(array|string $meta, mixed $value = null): static
        {
            $this->contentObj->setMeta($meta, $value);

            return $this;
        }
    }
}

namespace Laravel\Mcp\Server {

    use Illuminate\Contracts\JsonSchema\JsonSchema;
    use Illuminate\Http\Request;
    use Illuminate\JsonSchema\Types\Type;
    use Laravel\Mcp\Response;

    /**
     * Stub for Laravel\Mcp\Server\Tool.
     */
    abstract class Tool
    {
        abstract public function name(): string;

        public function title(): string
        {
            return $this->name();
        }

        abstract public function description(): string;

        /** @return array<string, Type> */
        public function schema(JsonSchema $schema): array
        {
            return [];
        }

        /** @return array<string, Type> */
        public function outputSchema(JsonSchema $schema): array
        {
            return [];
        }

        /** @return array<string, mixed> */
        public function annotations(): array
        {
            return [];
        }

        /** @return Response|array<int, Response> */
        abstract public function handle(Request $request): Response|array;
    }
}

namespace Laravel\Mcp\Server\Tools\Annotations {

    #[\Attribute(\Attribute::TARGET_CLASS)]
    class IsReadOnly
    {
        public function __construct(public bool $value = true) {}

        public function key(): string
        {
            return 'readOnlyHint';
        }
    }

    #[\Attribute(\Attribute::TARGET_CLASS)]
    class IsDestructive
    {
        public function __construct(public bool $value = true) {}

        public function key(): string
        {
            return 'destructiveHint';
        }
    }

    #[\Attribute(\Attribute::TARGET_CLASS)]
    class IsIdempotent
    {
        public function __construct(public bool $value = true) {}

        public function key(): string
        {
            return 'idempotentHint';
        }
    }

    #[\Attribute(\Attribute::TARGET_CLASS)]
    class IsOpenWorld
    {
        public function __construct(public bool $value = true) {}

        public function key(): string
        {
            return 'openWorldHint';
        }
    }
}
