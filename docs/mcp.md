# MCP Bridge (opt-in)

`laravel-ai-action` ships an opt-in bridge that exposes any `AgentAction` as a
[Laravel MCP](https://laravel.com/docs/13.x/mcp) tool. Claude Desktop, Cursor,
and any MCP-aware client can then call your existing actions directly over the
Model Context Protocol.

## Requirements

- `pixelworxio/laravel-ai-action` installed
- `laravel/mcp >=0.1` installed (`composer require laravel/mcp`)
- PHP 8.4+

## Setup

### 1 — Install laravel/mcp

```bash
composer require laravel/mcp
```

### 2 — Enable the bridge

In your `.env`:

```env
AI_ACTION_MCP_ENABLED=true
```

Or in `config/ai-action.php`:

```php
'mcp' => [
    'enabled' => true,
    'discover_in' => [],
    'cache_discovery' => true,
],
```

### 3 — Implement ExposedAsMcpTool on your action

```php
<?php

namespace App\Ai\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Pixelworxio\LaravelAiAction\Actions\RunAgentAction;
use Pixelworxio\LaravelAiAction\Concerns\InteractsWithAgent;
use Pixelworxio\LaravelAiAction\Contracts\AgentAction;
use Pixelworxio\LaravelAiAction\DTOs\AgentContext;
use Pixelworxio\LaravelAiAction\DTOs\AgentResult;
use Pixelworxio\LaravelAiAction\Mcp\Attributes\ExposesAsMcpTool;
use Pixelworxio\LaravelAiAction\Mcp\Concerns\BridgesAgentContextToMcp;
use Pixelworxio\LaravelAiAction\Mcp\Contracts\ExposedAsMcpTool;

#[ExposesAsMcpTool]
#[IsReadOnly]
final class SummarizeInvoice implements AgentAction, ExposedAsMcpTool
{
    use InteractsWithAgent;
    use BridgesAgentContextToMcp;

    public function instructions(AgentContext $context): string
    {
        return 'Summarize the invoice for a finance reviewer.';
    }

    public function prompt(AgentContext $context): string
    {
        $invoice = $context->record;
        return "Invoice #{$invoice->number}: {$invoice->line_items->toJson()}";
    }

    public function handle(AgentContext $context): AgentResult
    {
        return app(RunAgentAction::class)->execute($this, $context);
    }

    // ── MCP surface ─────────────────────────────────────────────────────────

    public function mcpName(): string
    {
        return 'summarize_invoice';
    }

    public function mcpDescription(): string
    {
        return 'Summarize a single invoice and surface any red flags for finance review.';
    }

    public function mcpInputSchema(JsonSchema $schema): array
    {
        return [
            'invoice_id' => $schema->integer()->required()->description('Invoice primary key.'),
        ];
    }

    public function resolveContext(array $input, ?Authenticatable $user): AgentContext
    {
        // Auth-scoped resolution — tenant scopes and global scopes apply automatically.
        $invoice = $this->resolveRecord(\App\Models\Invoice::class, $input['invoice_id'], $user);

        return AgentContext::fromRecord($invoice);
    }
}
```

### 4 — Register the tool

**Explicit registration** (recommended — canonical, supports overrides):

```php
// In your AppServiceProvider::boot() or a dedicated routes/ai.php:

use Pixelworxio\LaravelAiAction\Mcp\Facades\AiActionMcp;

AiActionMcp::tool(\App\Ai\Actions\SummarizeInvoice::class);

// Optional name override:
AiActionMcp::tool(\App\Ai\Actions\DraftReply::class)->name('draft_reply_v2');
```

**Auto-discovery** (secondary — attribute-driven):

```php
// config/ai-action.php
'mcp' => [
    'enabled' => true,
    'discover_in' => [app_path('Ai/Actions')],
    'cache_discovery' => true,
],
```

Any class carrying `#[ExposesAsMcpTool]` under the configured paths is
auto-registered. Explicit registrations always take precedence over discovered
ones.

## Generating MCP-ready actions

```bash
php artisan make:ai-action SummarizeInvoice --mcp
```

Emits a stub already implementing `ExposedAsMcpTool` and using the
`BridgesAgentContextToMcp` trait.

## Annotations

Declare MCP annotations on the action class itself. The adapter forwards them
to the protocol layer automatically:

```php
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsReadOnly]          // safe to call; no side effects
#[IsIdempotent]        // same call produces the same result
final class SummarizeInvoice implements AgentAction, ExposedAsMcpTool { ... }
```

## Auth scoping

The bridge delivers the authenticated user (from the MCP HTTP transport, via
`$request->user()`) to `resolveContext()` as `$user`. What you do with it is
entirely your responsibility — the bridge enforces nothing:

```php
public function resolveContext(array $input, ?Authenticatable $user): AgentContext
{
    // Scope the query to the authenticated user's tenant:
    $invoice = Invoice::query()
        ->whereBelongsTo($user->currentTeam)
        ->findOrFail($input['invoice_id']);

    return AgentContext::fromRecord($invoice);
}
```

Throw `InvalidContextException` if the input is invalid or the record is not
accessible; the bridge maps it to `Response::error()` automatically.

## Custom response formatting

For actions that need multi-content responses (image + text, multiple
structured results, etc.) implement a `formatMcpResponse()` method directly on
the action:

```php
public function formatMcpResponse(AgentResult $result): \Laravel\Mcp\Response
{
    return \Laravel\Mcp\Response::structured([
        'summary' => $result->structured['summary'],
        'model' => $result->model,
    ]);
}
```

## Token metadata

Token counts and provider/model details are attached to every response's `_meta`
automatically:

```json
{
  "_meta": {
    "ai-action": {
      "provider": "anthropic",
      "model": "claude-sonnet-4-20250514",
      "input_tokens": 843,
      "output_tokens": 127
    }
  }
}
```

## Disabling the bridge when laravel/mcp is absent

Leave `AI_ACTION_MCP_ENABLED=false` (the default) or simply do not install
`laravel/mcp`. The bridge classes are PSR-4 autoloadable but no file is
parsed or loaded at runtime — the service provider short-circuits before
touching any bridge code.

## CI matrix

Add a no-MCP lane to your CI to ensure the package works without the optional
dependency:

```yaml
# .github/workflows/tests.yml (excerpt)
- name: Test (no-MCP lane)
  run: vendor/bin/pest --exclude-group=mcp
```
