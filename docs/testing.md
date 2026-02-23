# Testing

`laravel-ai-action` ships a `FakeAgentAction` test double that replaces `RunAgentAction` in the container. No real API calls are made.

## Setup

```php
use Pixelworxio\LaravelAiAction\Testing\FakeAgentAction;

beforeEach(function (): void {
    FakeAgentAction::fake();
});
```

## Registering Fake Responses

```php
FakeAgentAction::fakeResponse(SummarizePost::class, 'This post covers Laravel testing.');

// With structured output
FakeAgentAction::fakeResponse(ClassifyPost::class, '', [
    'topic'      => 'Laravel',
    'sentiment'  => 'positive',
    'confidence' => 0.95,
]);
```

## Assertions

```php
it('summarizes a post', function (): void {
    FakeAgentAction::fakeResponse(SummarizePost::class, 'This post covers Laravel testing.');

    $context = AgentContext::fromRecord($this->post);
    $result  = app(RunAgentAction::class)->execute(new SummarizePost(), $context);

    FakeAgentAction::assertAgentCalled(SummarizePost::class);
    FakeAgentAction::assertAgentCalled(SummarizePost::class, times: 1);
    FakeAgentAction::assertAgentNotCalled(ClassifyPost::class);

    expect($result->text)->toBe('This post covers Laravel testing.');
});
```

## Fluent Result Assertions

```php
use Pixelworxio\LaravelAiAction\Testing\AgentActionAssertions;

AgentActionAssertions::for($result)
    ->assertText('This post covers Laravel testing.')
    ->assertTextContains('Laravel')
    ->assertIsText()
    ->assertProvider('anthropic')
    ->assertModel('claude-sonnet-4-20250514')
    ->assertInputTokens(0)
    ->assertOutputTokens(0);

// For structured results
AgentActionAssertions::for($result)
    ->assertIsStructured()
    ->assertStructured(fn ($value) => $value->topic === 'Laravel');
```

## Resetting Between Tests

`FakeAgentAction::fake()` resets automatically when called in `beforeEach`. For manual resets:

```php
FakeAgentAction::reset();
```
