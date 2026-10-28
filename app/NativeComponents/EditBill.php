<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Services\Api\ApiException;
use App\Services\Api\ApiSession;
use App\Services\Api\LivemaasApi;

/**
 * The Add a bill screen, filled in with an existing bill. Saving replaces
 * its details; photos picked here are added to the ones it already has.
 */
class EditBill extends NewBill
{
    public int $billId = 0;

    /** How many photos the bill already has, shown above the picker. */
    public int $existingPhotos = 0;

    /**
     * The card this bill was paid with, kept in the picker even if it has
     * since been removed from Cards & accounts.
     *
     * @var array<string, mixed>|null
     */
    public ?array $currentCard = null;

    public function mount(LivemaasApi $api, ApiSession $session, int $id = 0): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        $this->billId = $id;

        try {
            $bill = $api->bill($id);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->currentCard = $bill['payment_method'];
        $this->loadOptions($api);

        $this->payee = $bill['payee'];
        $this->amount = number_format($bill['amount_cents'] / 100, 2, '.', '');
        $this->category = $bill['category_label'];
        $this->paidOn = $bill['paid_on'];
        $this->paidBy = $bill['paid_by']['name'] ?? '';
        $this->paymentMethod = $bill['payment_method']['display_name'] ?? self::NoCard;
        $this->notes = $bill['notes'] ?? '';
        $this->isPrivate = (bool) ($bill['is_private'] ?? false);
        $this->existingPhotos = count($bill['attachments'] ?? []);
    }

    public function navTitle(): string
    {
        return 'Edit bill';
    }

    protected function loadOptions(LivemaasApi $api): void
    {
        parent::loadOptions($api);

        $cards = $this->options['payment_methods'] ?? [];

        if ($this->currentCard !== null && ! in_array($this->currentCard['id'], array_column($cards, 'id'), true)) {
            $this->options['payment_methods'] = [...$cards, $this->currentCard];
        }
    }

    /**
     * @param  array<string, mixed>  $bill
     */
    protected function persist(LivemaasApi $api, array $bill): void
    {
        $api->updateBill($this->billId, $bill);
    }
}
