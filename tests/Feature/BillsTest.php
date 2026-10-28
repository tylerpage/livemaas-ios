<?php

declare(strict_types=1);

use App\NativeComponents\BillDetail;
use App\NativeComponents\Bills;
use App\NativeComponents\NewBill;
use App\NativeComponents\NewPaymentMethod;
use App\Services\Api\ApiSession;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Native\Mobile\AsyncTask;
use Native\Mobile\Testing\Native;

beforeEach(function (): void {
    app(ApiSession::class)->start('1|secret-token', 1, 'Tyler');
    AsyncTask::fake();
    $this->travelTo('2026-10-27 12:00:00');
});

function billOptions(): array
{
    return ['data' => [
        'members' => [['id' => 1, 'name' => 'Tyler'], ['id' => 2, 'name' => 'Sam']],
        'payment_methods' => [['id' => 7, 'nickname' => 'Amex Gold', 'display_name' => 'Amex Gold ••1005']],
        'categories' => [['value' => 'mortgage', 'label' => 'Mortgage'], ['value' => 'utilities', 'label' => 'Utilities']],
        'payment_method_types' => [['value' => 'credit_card', 'label' => 'Credit card'], ['value' => 'cash', 'label' => 'Cash']],
    ]];
}

it('shows the month total, who paid, and the running list', function (): void {
    Http::fake([
        'api.test/api/v1/bills/summary*' => Http::response(['data' => [
            'month' => '2026-10', 'total_cents' => 166_237, 'count' => 2,
            'by_member' => [['name' => 'Tyler', 'total_cents' => 152_000], ['name' => 'Sam', 'total_cents' => 14_237]],
            'by_payment_method' => [['name' => 'Amex Gold ••1005', 'total_cents' => 166_237]],
            'by_category' => [],
        ]]),
        'api.test/api/v1/bills?*' => Http::response(['data' => [
            ['id' => 3, 'payee' => 'Xcel Energy', 'amount_cents' => 14_237, 'paid_on' => '2026-10-20',
                'paid_by' => ['name' => 'Sam'], 'payment_method' => ['display_name' => 'Amex Gold ••1005'], 'attachment_count' => 1],
        ]]),
    ]);

    Native::test(Bills::class)
        ->assertSee('October 2026')
        ->assertSee('$1,662.37')
        ->assertSee('2 bills')
        ->assertSee('$1,520.00')
        ->assertSee('Xcel Energy')
        ->assertSee('$142.37')
        ->tap('bill-3')
        ->assertNavigatedTo('/bills/3');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'bills?month=2026-10'));
});

it('steps back a month but not into the future', function (): void {
    Http::fake([
        'api.test/api/v1/bills/summary*' => Http::response(['data' => ['total_cents' => 0, 'count' => 0, 'by_member' => [], 'by_payment_method' => [], 'by_category' => []]]),
        'api.test/api/v1/bills?*' => Http::response(['data' => []]),
    ]);

    Native::test(Bills::class)
        ->tap('next-month')
        ->assertSet('month', '2026-10')
        ->tap('previous-month')
        ->assertSet('month', '2026-09')
        ->assertSee('September 2026')
        ->assertSee('No bills recorded for September 2026.');
});

it('records a bill, mapping picker labels back to ids', function (): void {
    Http::fake([
        'api.test/api/v1/bills/options' => Http::response(billOptions()),
        'api.test/api/v1/bills' => Http::response(['data' => ['id' => 9]], 201),
    ]);

    Native::test(NewBill::class)
        ->assertSet('paidBy', 'Tyler')
        ->assertSet('paidOn', '2026-10-27')
        ->set('payee', ' Xcel Energy ')
        ->set('amount', '$142.37')
        ->set('category', 'Utilities')
        ->set('paidBy', 'Sam')
        ->set('paymentMethod', 'Amex Gold ••1005')
        ->tap('save')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.test/api/v1/bills'
        && $request['payee'] === 'Xcel Energy'
        && $request['amount_cents'] === 14_237
        && $request['category'] === 'utilities'
        && $request['paid_by_id'] === 2
        && $request['payment_method_id'] === 7
        && $request['paid_on'] === '2026-10-27');
});

it('catches a bad amount before calling the API', function (): void {
    Http::fake(['api.test/api/v1/bills/options' => Http::response(billOptions())]);

    Native::test(NewBill::class)
        ->set('payee', 'Xcel Energy')
        ->set('amount', 'twelve')
        ->tap('save')
        ->assertSee('Enter an amount like 142.37.')
        ->assertNoNavigation();

    Http::assertSentCount(1);
});

it('shows a bill with its photos and deletes it', function (): void {
    Http::fake([
        'api.test/api/v1/bills/3' => Http::sequence()
            ->push(['data' => [
                'id' => 3, 'payee' => 'Xcel Energy', 'amount_cents' => 14_237, 'paid_on' => '2026-10-20',
                'category_label' => 'Utilities', 'notes' => 'Higher than usual — AC.',
                'paid_by' => ['name' => 'Sam'], 'created_by' => ['name' => 'Tyler'],
                'payment_method' => ['display_name' => 'Amex Gold ••1005'],
                'attachments' => [['id' => 'a1', 'mime_type' => 'image/jpeg', 'url' => 'https://bucket.test/a1.jpg', 'original_name' => 'bill.jpg']],
            ]])
            ->push(null, 204),
    ]);

    Native::test(BillDetail::class, ['id' => 3])
        ->assertSee('$142.37')
        ->assertSee('Tuesday, October 20, 2026')
        ->assertSee('Amex Gold ••1005')
        ->assertSee('Higher than usual — AC.')
        ->assertElement('image', fn (array $node): bool => ($node['props']['src'] ?? null) === 'https://bucket.test/a1.jpg')
        ->tap('delete')
        ->tap('confirm-delete')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE');
});

it('adds a card with only the last four digits', function (): void {
    Http::fake([
        'api.test/api/v1/bills/options' => Http::response(billOptions()),
        'api.test/api/v1/payment-methods' => Http::response(['data' => ['id' => 8]], 201),
    ]);

    Native::test(NewPaymentMethod::class)
        ->set('nickname', 'Chase Sapphire')
        ->set('last4', '4242424242424242')
        ->tap('save')
        ->assertSee('Just the last four digits.')
        ->set('last4', '4242')
        ->set('owner', 'Sam')
        ->tap('save')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request['last4'] === '4242'
        && $request['type'] === 'credit_card'
        && $request['owner_id'] === 2);
});

it('shows who owes whom for the month', function (): void {
    Http::fake([
        'api.test/api/v1/bills/summary*' => Http::response(['data' => [
            'total_cents' => 40_000, 'count' => 2, 'by_member' => [], 'by_payment_method' => [], 'by_category' => [],
            'settle_up' => [
                'members' => [
                    ['id' => 2, 'name' => 'Lindsey', 'share_percent' => 50.0, 'paid_cents' => 10_000, 'share_cents' => 20_000, 'balance_cents' => -10_000],
                    ['id' => 1, 'name' => 'Tyler', 'share_percent' => 50.0, 'paid_cents' => 30_000, 'share_cents' => 20_000, 'balance_cents' => 10_000],
                ],
                'transfers' => [['from' => ['id' => 2, 'name' => 'Lindsey'], 'to' => ['id' => 1, 'name' => 'Tyler'], 'amount_cents' => 10_000]],
            ],
        ]]),
        'api.test/api/v1/bills*' => Http::response(['data' => []]),
    ]);

    Native::test(Bills::class)
        ->assertSee('Settle up')
        ->assertSee('$100.00')
        ->assertDontSee('Lindsey · 50%')
        ->tap('split-details')
        ->assertSee('Lindsey · 50%')
        ->assertSee('paid $300.00 of $200.00')
        ->tap('change-split')
        ->assertNavigatedTo('/bill-split');
});

it('saves a private bill', function (): void {
    Http::fake([
        'api.test/api/v1/bills/options' => Http::response(billOptions()),
        'api.test/api/v1/bills' => Http::response(['data' => ['id' => 9]], 201),
    ]);

    Native::test(NewBill::class)
        ->set('payee', 'Birthday gift')
        ->set('amount', '50')
        ->set('isPrivate', true)
        ->tap('save');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && str_ends_with($request->url(), '/bills')
        && $request['is_private'] === true);
});
