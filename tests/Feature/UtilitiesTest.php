<?php

declare(strict_types=1);

use App\NativeComponents\EditUtility;
use App\NativeComponents\More;
use App\NativeComponents\NewUtility;
use App\NativeComponents\Utilities;
use App\NativeComponents\UtilityDetail;
use App\Services\Api\ApiSession;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Native\Mobile\AsyncTask;
use Native\Mobile\Testing\Native;

beforeEach(function (): void {
    app(ApiSession::class)->start('1|secret-token', 1, 'Tyler');
    AsyncTask::fake();
});

function utilityOptions(): array
{
    return ['data' => [
        'kinds' => [['value' => 'electric', 'label' => 'Electric'], ['value' => 'gas', 'label' => 'Gas']],
        'payment_methods' => [['id' => 7, 'nickname' => 'Amex Gold', 'display_name' => 'Amex Gold ••1005']],
    ]];
}

function apiUtility(array $overrides = []): array
{
    return array_replace([
        'id' => 3,
        'name' => 'Xcel Energy',
        'kind' => 'electric',
        'kind_label' => 'Electric',
        'payment_method' => ['id' => 7, 'display_name' => 'Amex Gold ••1005', 'archived' => false],
        'website' => 'https://xcelenergy.com',
        'account_number' => '51-0012345-6',
        'username' => 'house@example.com',
        'password' => 'hunter2!',
        'login_notes' => 'Code is texted to Tyler’s phone.',
        'created_by' => ['name' => 'Tyler'],
        'created_at' => '2026-10-30T10:00:00+00:00',
    ], $overrides);
}

it('opens utilities from More', function (): void {
    Native::test(More::class)->tap('utilities')->assertNavigatedTo('/utilities');
});

it('lists utilities with the card that pays each', function (): void {
    Http::fake(['api.test/api/v1/utilities' => Http::response(['data' => [
        apiUtility(),
        apiUtility(['id' => 4, 'name' => 'CenterPoint', 'kind_label' => 'Gas', 'payment_method' => null]),
    ]])]);

    Native::test(Utilities::class)
        ->assertSee('Electric · Amex Gold ••1005')
        ->assertSee('Gas · No card set')
        ->tap('utility-3')
        ->assertNavigatedTo('/utilities/3');
});

it('adds a utility with its card and sign-in', function (): void {
    Http::fake([
        'api.test/api/v1/utilities/options' => Http::response(utilityOptions()),
        'api.test/api/v1/utilities' => Http::response(['data' => apiUtility()], 201),
    ]);

    Native::test(NewUtility::class)
        ->set('name', ' Xcel Energy ')
        ->set('kind', 'Electric')
        ->set('paymentMethod', 'Amex Gold ••1005')
        ->set('accountNumber', '51-0012345-6')
        ->set('website', 'xcelenergy.com')
        ->set('username', 'house@example.com')
        ->set('password', ' hunter2! ')
        ->tap('save')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.test/api/v1/utilities'
        && $request['name'] === 'Xcel Energy'
        && $request['kind'] === 'electric'
        && $request['payment_method_id'] === 7
        && $request['website'] === 'xcelenergy.com'
        && $request['password'] === ' hunter2! '
        && $request['login_notes'] === null);
});

it('needs a name before saving', function (): void {
    Http::fake(['api.test/api/v1/utilities/options' => Http::response(utilityOptions())]);

    Native::test(NewUtility::class)
        ->tap('save')
        ->assertSee('Give it a name, like "Xcel Energy".')
        ->assertNoNavigation();

    Http::assertNotSent(fn (Request $request): bool => $request->method() === 'POST');
});

it('masks the password until Show is tapped', function (): void {
    Http::fake(['api.test/api/v1/utilities/3' => Http::response(['data' => apiUtility()])]);

    Native::test(UtilityDetail::class, ['id' => 3])
        ->assertSee('51-0012345-6')
        ->assertSee('house@example.com')
        ->assertSee('Code is texted to Tyler’s phone.')
        ->assertSee('••••••••')
        ->assertDontSee('hunter2!')
        ->tap('toggle-password')
        ->assertSee('hunter2!');
});

it('opens the utility website in the in-app browser', function (): void {
    Http::fake(['api.test/api/v1/utilities/3' => Http::response(['data' => apiUtility()])]);
    $bridge = Native::fakeBridge()->respondTo('Browser.OpenInApp', ['success' => true]);

    Native::test(UtilityDetail::class, ['id' => 3])
        ->assertSee('xcelenergy.com')
        ->tap('website');

    $bridge->assertCalled('Browser.OpenInApp', fn (array $params): bool => $params['url'] === 'https://xcelenergy.com');
});

it('edits a utility paid with an archived card', function (): void {
    Http::fake([
        'api.test/api/v1/utilities/options' => Http::response(utilityOptions()),
        'api.test/api/v1/utilities/3' => Http::response(['data' => apiUtility([
            'payment_method' => ['id' => 9, 'display_name' => 'Old Visa ••0001', 'archived' => true],
        ])]),
    ]);

    Native::test(EditUtility::class, ['id' => 3])
        ->assertSet('name', 'Xcel Energy')
        ->assertSet('paymentMethod', 'Old Visa ••0001')
        ->assertSet('password', 'hunter2!')
        ->set('kind', 'Gas')
        ->tap('save')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
        && str_ends_with($request->url(), '/utilities/3')
        && $request['kind'] === 'gas'
        && $request['payment_method_id'] === 9);
});

it('deletes a utility after confirming', function (): void {
    Http::fake(['api.test/api/v1/utilities/3' => Http::response(['data' => apiUtility()])]);

    Native::test(UtilityDetail::class, ['id' => 3])
        ->tap('delete')
        ->tap('confirm-delete')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE' && str_ends_with($request->url(), '/utilities/3'));
});
