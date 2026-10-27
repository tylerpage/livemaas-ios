<?php

declare(strict_types=1);

use App\NativeComponents\Login;
use App\NativeComponents\More;
use App\NativeComponents\Timeline;
use App\Services\Api\ApiSession;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

it('signs in, stores the token and goes to the timeline', function (): void {
    Http::fake([
        'api.test/api/v1/auth/token' => Http::response([
            'token' => '1|secret-token',
            'user' => ['id' => 1, 'name' => 'Tyler', 'email' => 'tyler@example.com'],
        ], 201),
    ]);

    Native::test(Login::class)
        ->set('email', 'tyler@example.com')
        ->set('password', 'correct-horse-battery')
        ->tap('sign-in')
        ->assertReplacedWith('/')
        ->assertSet('password', '');

    $session = app(ApiSession::class);

    expect($session->token())->toBe('1|secret-token')
        ->and($session->userName())->toBe('Tyler');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.test/api/v1/auth/token'
        && $request['email'] === 'tyler@example.com'
        && $request['device_name'] === 'iPhone');
});

it('shows the server message for bad credentials', function (): void {
    Http::fake([
        'api.test/*' => Http::response([
            'message' => 'These credentials do not match our records.',
            'errors' => ['email' => ['These credentials do not match our records.']],
        ], 422),
    ]);

    Native::test(Login::class)
        ->set('email', 'tyler@example.com')
        ->set('password', 'wrong')
        ->tap('sign-in')
        ->assertSee('These credentials do not match our records.')
        ->assertNoNavigation();

    expect(app(ApiSession::class)->isSignedIn())->toBeFalse();
});

it('explains when the server cannot be reached', function (): void {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    Native::test(Login::class)
        ->set('email', 'tyler@example.com')
        ->set('password', 'correct-horse-battery')
        ->tap('sign-in')
        ->assertSee('Could not reach the house server. Check your connection and try again.');
});

it('sends signed-out users to the login screen', function (): void {
    Native::test(Timeline::class)->assertReplacedWith('/login');
});

it('stores the token encrypted at rest', function (): void {
    app(ApiSession::class)->start('1|secret-token', 'Tyler');

    $raw = DB::table('device_settings')->where('key', 'api_token')->value('value');

    expect($raw)->not->toContain('secret-token');
});

it('signs out even when the server is unreachable', function (): void {
    app(ApiSession::class)->start('1|secret-token', 'Tyler');
    Http::fake(fn () => throw new ConnectionException('offline'));

    Native::test(More::class)
        ->assertSee('Tyler')
        ->tap('sign-out')
        ->assertReplacedWith('/login');

    expect(app(ApiSession::class)->isSignedIn())->toBeFalse();
});

it('shows the five house tabs', function (): void {
    app(ApiSession::class)->start('1|secret-token', 'Tyler');

    Native::visit('/')
        ->assertHasTabBar()
        ->assertNavTitle('Timeline')
        ->assertSee('Nothing here yet');
});
