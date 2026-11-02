<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;

/**
 * The Add a utility screen, filled in with an existing one.
 */
class EditUtility extends NewUtility
{
    public int $utilityId = 0;

    public function mount(LivemaasApi $api, int $id = 0): void
    {
        parent::mount($api);
        $this->utilityId = $id;

        try {
            $utility = $api->utility($id);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $card = $utility['payment_method'] ?? null;

        // An archived card still paying this utility stays pickable while editing it.
        if ($card !== null && ! in_array($card['id'], array_column($this->paymentMethods, 'id'), true)) {
            $this->paymentMethods[] = $card;
        }

        $this->name = $utility['name'];
        $this->kind = $utility['kind_label'];
        $this->paymentMethod = $card['display_name'] ?? self::NoCard;
        $this->accountNumber = $utility['account_number'] ?? '';
        $this->website = $utility['website'] ?? '';
        $this->username = $utility['username'] ?? '';
        $this->password = $utility['password'] ?? '';
        $this->loginNotes = $utility['login_notes'] ?? '';
    }

    public function navTitle(): string
    {
        return 'Edit utility';
    }

    /**
     * @param  array<string, mixed>  $utility
     */
    protected function persist(LivemaasApi $api, array $utility): void
    {
        $api->updateUtility($this->utilityId, $utility);
    }
}
