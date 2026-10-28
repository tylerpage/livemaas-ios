<?php

declare(strict_types=1);

namespace App\Services\Api;

use GuzzleHttp\Psr7\Utils;
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

        $this->session->start($body['token'], $body['user']['id'], $body['user']['name']);

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
     * One page of house logs, newest first.
     *
     * @return array{posts: list<array<string, mixed>>, next_cursor: string|null}
     */
    public function posts(?string $cursor = null): array
    {
        $body = $this->send(fn (PendingRequest $http): Response => $http->get('posts', array_filter(['cursor' => $cursor])));

        return ['posts' => $body['data'], 'next_cursor' => $body['meta']['next_cursor'] ?? null];
    }

    /**
     * @param  list<string>  $attachmentIds
     * @return array<string, mixed>
     */
    public function createPost(?string $body, array $attachmentIds): array
    {
        return $this->send(fn (PendingRequest $http): Response => $http->post('posts', [
            'body' => $body,
            'attachment_ids' => $attachmentIds,
        ]))['data'];
    }

    public function deletePost(int $postId): void
    {
        $this->send(fn (PendingRequest $http): Response => $http->delete("posts/{$postId}"));
    }

    /**
     * @return array<string, mixed> members, payment_methods, categories, payment_method_types
     */
    public function billOptions(): array
    {
        return $this->send(fn (PendingRequest $http): Response => $http->get('bills/options'))['data'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function bills(string $month): array
    {
        return $this->send(fn (PendingRequest $http): Response => $http->get('bills', ['month' => $month]))['data'];
    }

    /**
     * @return array<string, mixed>
     */
    public function billSummary(string $month): array
    {
        return $this->send(fn (PendingRequest $http): Response => $http->get('bills/summary', ['month' => $month]))['data'];
    }

    /**
     * @return array<string, mixed>
     */
    public function bill(int $billId): array
    {
        return $this->send(fn (PendingRequest $http): Response => $http->get("bills/{$billId}"))['data'];
    }

    /**
     * @param  array<string, mixed>  $bill
     * @return array<string, mixed>
     */
    public function createBill(array $bill): array
    {
        return $this->send(fn (PendingRequest $http): Response => $http->post('bills', $bill))['data'];
    }

    public function deleteBill(int $billId): void
    {
        $this->send(fn (PendingRequest $http): Response => $http->delete("bills/{$billId}"));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function paymentMethods(): array
    {
        return $this->send(fn (PendingRequest $http): Response => $http->get('payment-methods'))['data'];
    }

    /**
     * @param  array<string, mixed>  $paymentMethod
     * @return array<string, mixed>
     */
    public function createPaymentMethod(array $paymentMethod): array
    {
        return $this->send(fn (PendingRequest $http): Response => $http->post('payment-methods', $paymentMethod))['data'];
    }

    public function archivePaymentMethod(int $paymentMethodId): void
    {
        $this->send(fn (PendingRequest $http): Response => $http->delete("payment-methods/{$paymentMethodId}"));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function documentCategories(): array
    {
        return $this->send(fn (PendingRequest $http): Response => $http->get('documents/options'))['data']['categories'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function documents(?string $category = null, ?string $search = null): array
    {
        return $this->send(fn (PendingRequest $http): Response => $http->get('documents', array_filter([
            'category' => $category,
            'search' => $search,
        ])))['data'];
    }

    /**
     * @return array<string, mixed>
     */
    public function document(int $documentId): array
    {
        return $this->send(fn (PendingRequest $http): Response => $http->get("documents/{$documentId}"))['data'];
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    public function createDocument(array $document): array
    {
        return $this->send(fn (PendingRequest $http): Response => $http->post('documents', $document))['data'];
    }

    public function deleteDocument(int $documentId): void
    {
        $this->send(fn (PendingRequest $http): Response => $http->delete("documents/{$documentId}"));
    }

    /**
     * Upload a file from the phone straight to the bucket: presign, PUT the
     * bytes to the signed URL, then confirm. The file is streamed from disk so
     * a long video never has to fit in memory.
     *
     * @return array<string, mixed> The confirmed attachment.
     */
    public function uploadAttachment(string $path, string $mimeType): array
    {
        if (! is_readable($path)) {
            throw new ApiException('That file could not be read from the phone.');
        }

        $presigned = $this->send(fn (PendingRequest $http): Response => $http->post('attachments/presign', [
            'filename' => basename($path),
            'mime_type' => $mimeType,
            'size_bytes' => filesize($path),
        ]));

        try {
            $upload = Http::withHeaders($presigned['upload']['headers'])
                ->timeout(config('livemaas.upload_timeout_seconds'))
                ->withBody(Utils::streamFor(fopen($path, 'r')), $mimeType)
                ->put($presigned['upload']['url']);
        } catch (ConnectionException) {
            throw new ApiException('The upload was interrupted. Check your connection and try again.');
        }

        if ($upload->failed()) {
            throw new ApiException('The upload was rejected by storage.', $upload->status());
        }

        $attachmentId = $presigned['attachment']['id'];

        return $this->send(fn (PendingRequest $http): Response => $http->post("attachments/{$attachmentId}/confirm"))['data'];
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
