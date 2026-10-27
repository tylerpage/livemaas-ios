<?php

declare(strict_types=1);

namespace App\Services\Api;

use RuntimeException;

/**
 * A failed API call, carrying a message fit to show on screen.
 */
class ApiException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors  Laravel validation errors, keyed by field.
     */
    public function __construct(string $message, public readonly int $status = 0, public readonly array $errors = [])
    {
        parent::__construct($message);
    }

    public function isUnauthenticated(): bool
    {
        return $this->status === 401;
    }
}
