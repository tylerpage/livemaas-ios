<?php

declare(strict_types=1);

use App\NativeComponents\NewPost;
use App\NativeComponents\Timeline;
use App\Services\Api\ApiSession;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Native\Mobile\AsyncTask;
use Native\Mobile\Events\Gallery\MediaSelected;
use Native\Mobile\Testing\Native;

beforeEach(function (): void {
    app(ApiSession::class)->start('1|secret-token', 1, 'Tyler');
    AsyncTask::fake();
});

function apiPost(array $overrides = []): array
{
    return array_replace([
        'id' => 1,
        'body' => 'Furnace filter swapped.',
        'author' => ['id' => 1, 'name' => 'Tyler', 'email' => 'tyler@example.com'],
        'attachments' => [],
        'can_edit' => true,
        'created_at' => now()->subHour()->toIso8601String(),
        'updated_at' => now()->subHour()->toIso8601String(),
    ], $overrides);
}

it('shows house logs from the API', function (): void {
    Http::fake([
        'api.test/api/v1/posts*' => Http::response([
            'data' => [apiPost(), apiPost(['id' => 2, 'body' => 'Gutters cleaned.', 'can_edit' => false])],
            'meta' => ['next_cursor' => 'abc'],
        ]),
    ]);

    Native::test(Timeline::class)
        ->assertSee('Furnace filter swapped.')
        ->assertSee('Gutters cleaned.')
        ->assertSee('1 hour ago')
        ->assertSee('Load older logs')
        ->assertSet('nextCursor', 'abc');

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer 1|secret-token'));
});

it('opens a photo full screen when tapped and closes it on dismiss', function (): void {
    Http::fake([
        'api.test/api/v1/posts*' => Http::response([
            'data' => [apiPost(['attachments' => [
                ['id' => '01JPHOTO', 'mime_type' => 'image/jpeg', 'url' => 'https://bucket.test/01JPHOTO.jpg'],
            ]])],
            'meta' => ['next_cursor' => null],
        ]),
    ]);

    Native::test(Timeline::class)
        ->assertMissingElement('modal')
        ->tap('photo-01JPHOTO')
        ->assertElement('modal')
        ->dismissSheet('photo-viewer')
        ->assertMissingElement('modal');
});

it('shows an empty state when there are no logs', function (): void {
    Http::fake(['api.test/*' => Http::response(['data' => [], 'meta' => ['next_cursor' => null]])]);

    Native::test(Timeline::class)->assertSee('No house logs yet');
});

it('sends the user to sign in when the token has been revoked', function (): void {
    Http::fake(['api.test/*' => Http::response(['message' => 'Unauthenticated.'], 401)]);

    Native::test(Timeline::class)->assertReplacedWith('/login');

    expect(app(ApiSession::class)->isSignedIn())->toBeFalse();
});

it('opens the composer from the + button', function (): void {
    Http::fake(['api.test/*' => Http::response(['data' => [], 'meta' => ['next_cursor' => null]])]);

    Native::test(Timeline::class)->tap('compose')->assertNavigatedTo('/posts/new');
});

it('posts text only', function (): void {
    Http::fake(['api.test/api/v1/posts' => Http::response(['data' => apiPost()], 201)]);

    Native::test(NewPost::class)
        ->set('body', 'Furnace filter swapped.')
        ->tap('post')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request['body'] === 'Furnace filter swapped.'
        && $request['attachment_ids'] === []);
});

it('uploads picked photos straight to the bucket, then posts them', function (): void {
    $photo = tempnam(sys_get_temp_dir(), 'photo');
    file_put_contents($photo, str_repeat('x', 2048));

    Http::fake([
        'api.test/api/v1/attachments/presign' => Http::response([
            'attachment' => ['id' => '01JPHOTO'],
            'upload' => ['method' => 'PUT', 'url' => 'https://bucket.test/attachments/01JPHOTO.jpg?sig=1', 'headers' => ['Content-Type' => 'image/jpeg']],
        ], 201),
        'bucket.test/*' => Http::response(),
        'api.test/api/v1/attachments/01JPHOTO/confirm' => Http::response(['data' => ['id' => '01JPHOTO', 'status' => 'uploaded']]),
        'api.test/api/v1/posts' => Http::response(['data' => apiPost()], 201),
    ]);

    Native::test(NewPost::class)
        ->emitNative(MediaSelected::class, [
            'success' => true,
            'files' => [['path' => $photo, 'mimeType' => 'image/jpeg', 'extension' => 'jpg', 'type' => 'image']],
            'count' => 1,
        ])
        ->assertSee('Ready')
        ->tap('post')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
        && $request->url() === 'https://bucket.test/attachments/01JPHOTO.jpg?sig=1'
        && strlen($request->body()) === 2048);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.test/api/v1/posts'
        && $request['attachment_ids'] === ['01JPHOTO']);
});

it('blocks posting while an upload has failed and lets the user retry', function (): void {
    $photo = tempnam(sys_get_temp_dir(), 'photo');
    file_put_contents($photo, 'x');

    Http::fake(['api.test/api/v1/attachments/presign' => Http::response(['message' => 'Server error'], 500)]);

    Native::test(NewPost::class)
        ->set('body', 'With a photo')
        ->emitNative(MediaSelected::class, [
            'success' => true,
            'files' => [['path' => $photo, 'mimeType' => 'image/jpeg']],
            'count' => 1,
        ])
        ->assertSee('Upload failed')
        ->assertSee('Try again')
        ->tap('post')
        ->assertNoNavigation();
});

it('ignores a cancelled picker', function (): void {
    Native::test(NewPost::class)
        ->emitNative(MediaSelected::class, ['success' => false, 'cancelled' => true])
        ->assertSet('media', []);
});
