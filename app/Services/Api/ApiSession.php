<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\DeviceSetting;

/**
 * Who is signed in on this phone. The Sanctum token is stored encrypted in the
 * app's own SQLite database.
 *
 * The SecureStorage plugin (iOS Keychain) was considered and deferred: it is a
 * paid NativePHP plugin, and the app sandbox plus at-rest encryption is
 * adequate for a two-person household app. Swap it in here if that changes —
 * nothing outside this class touches the token.
 */
class ApiSession
{
    private const string TokenKey = 'api_token';

    private const string UserNameKey = 'user_name';

    private const string UserIdKey = 'user_id';

    public function token(): ?string
    {
        return $this->get(self::TokenKey);
    }

    public function isSignedIn(): bool
    {
        return $this->token() !== null;
    }

    public function userName(): ?string
    {
        return $this->get(self::UserNameKey);
    }

    public function userId(): ?int
    {
        $id = $this->get(self::UserIdKey);

        return $id === null ? null : (int) $id;
    }

    public function start(string $token, int $userId, string $userName): void
    {
        $this->put(self::TokenKey, $token);
        $this->put(self::UserIdKey, (string) $userId);
        $this->put(self::UserNameKey, $userName);
    }

    public function end(): void
    {
        DeviceSetting::query()->whereIn('key', [self::TokenKey, self::UserIdKey, self::UserNameKey])->delete();
    }

    private function get(string $key): ?string
    {
        return DeviceSetting::query()->find($key)?->value;
    }

    private function put(string $key, string $value): void
    {
        DeviceSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
