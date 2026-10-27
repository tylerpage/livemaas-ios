<?php

declare(strict_types=1);

namespace App\Services\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * The one place this app talks HTTP. Screens call methods here and never
 * build requests themselves, so the token, base URL and error handling stay
 * consistent across every feature.
 */
class LivemaasApi
{
    public function __construct(private readonly ApiSession $session) {}

    /**
     * Exchange credentials for a token and remember who signed in.
     *
     * @return array{id: int, name: string, email: string}
     */
    public function signIn(string $email, string $password, string $deviceName): array
    {
        $body = $this->send(fn (PendingRequest $http): Response => $http->post('auth/token', [
            'email' => $email,
            'password' => $password,
            'device_name' => $deviceName,
        ]), authenticated: false);

        $this->session->start($body['token'], $body['user']['name']);

        return $body['user'];
    }

    /**
     * Revoke the token on the server, then forget it locally. The local
     * session always ends, even if the server is unreachable — otherwise a
     * dead connection would leave someone unable to sign out.
     */
    public function signOut(): void
    {
        try {
            $this->send(fn (PendingRequest $http): Response => $http->delete('auth/token'));
        } catch (ApiException) {
            // Already signed out locally below; a stale server token expires harmlessly.
        } finally {
            $this->session->end();
        }
    }

    /**
     * @return array{id: int, name: string, email: string}
     */
    public function me(): array
    {
        return $this->send(fn (PendingRequest $http): Response => $http->get('me'))['data'];
    }

    /**
     * @param  callable(PendingRequest): Response  $request
     * @return array<string, mixed>
     */
    private function send(callable $request, bool $authenticated = true): array
    {
        $http = Http::baseUrl(config('livemaas.api_url'))
            ->acceptJson()
            ->asJson()
            ->timeout(config('livemaas.api_timeout_seconds'));

        if ($authenticated) {
            $http->withToken((string) $this->session->token());
        }

        try {
            $response = $request($http);
        } catch (ConnectionException) {
            throw new ApiException('Could not reach the house server. Check your connection and try again.');
        }

        if ($response->status() === 401 && $authenticated) {
            $this->session->end();
        }

        if ($response->failed()) {
            throw new ApiException(
                $response->json('message') ?: 'Something went wrong. Please try again.',
                $response->status(),
                $response->json('errors') ?? [],
            );
        }

        return $response->json() ?? [];
    }
}
