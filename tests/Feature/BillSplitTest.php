<?php

declare(strict_types=1);

use App\NativeComponents\BillSplit;
use App\Services\Api\ApiSession;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

beforeEach(function (): void {
    app(ApiSession::class)->start('1|secret-token', 1, 'Tyler');
});

function splitMembers(?float $tyler = null, ?float $lindsey = null): array
{
    return ['data' => [
        'enabled' => $tyler !== null,
        'members' => [
            ['id' => 2, 'name' => 'Lindsey', 'share_percent' => $lindsey],
            ['id' => 1, 'name' => 'Tyler', 'share_percent' => $tyler],
        ],
    ]];
}

it('starts with an even split when bills are not split yet', function (): void {
    Http::fake(['api.test/api/v1/bill-split' => Http::response(splitMembers())]);

    Native::test(BillSplit::class)
        ->assertSet('percents', [2 => '50', 1 => '50'])
        ->assertSee('Save split')
        ->assertDontSee('Stop splitting bills');
});

it('saves the shares when they add up to 100%', function (): void {
    Http::fake(['api.test/api/v1/bill-split' => Http::response(splitMembers(50, 50))]);

    Native::test(BillSplit::class)
        ->set('percents', [2 => '40', 1 => '60'])
        ->tap('save');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
        && $request['shares'] === [['user_id' => 2, 'percent' => 40.0], ['user_id' => 1, 'percent' => 60.0]]);
});

it('will not save shares that do not add up', function (): void {
    Http::fake(['api.test/api/v1/bill-split' => Http::response(splitMembers(50, 50))]);

    Native::test(BillSplit::class)
        ->set('percents', [2 => '40', 1 => '50'])
        ->assertSee('Shares must add up to 100%')
        ->call('save')
        ->assertSee('The shares need to add up to 100%.');

    Http::assertNotSent(fn (Request $request): bool => $request->method() === 'PUT');
});

it('stops splitting bills', function (): void {
    Http::fake(['api.test/api/v1/bill-split' => Http::response(splitMembers(60, 40))]);

    Native::test(BillSplit::class)
        ->assertSet('percents', [2 => '40', 1 => '60'])
        ->tap('stop-splitting');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && $request['shares'] === []);
});
