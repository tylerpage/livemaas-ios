<?php

declare(strict_types=1);

namespace App\NativeComponents\Concerns;

use App\Services\Api\ApiSession;

/**
 * For screens that only make sense with an API token. Call from mount().
 */
trait RequiresSignIn
{
    /**
     * Send the user to the sign-in screen if this phone has no token.
     * Returns true when it redirected, so mount() can stop early.
     */
    protected function redirectIfSignedOut(): bool
    {
        if (app(ApiSession::class)->isSignedIn()) {
            return false;
        }

        $this->replace('/login');

        return true;
    }
}
