<?php

declare(strict_types=1);

namespace Pixelworxio\LaravelAiAction\Contracts;

/**
 * Allows an agent action to override Laravel AI's default request timeout.
 */
interface HasTimeout
{
    /**
     * Return the maximum duration, in seconds, for the provider request.
     */
    public function timeout(): int;
}
