<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\NativeComponents\Concerns\UploadsMedia;
use App\Services\Api\ApiException;
use App\Services\Api\ApiSession;
use App\Services\Api\LivemaasApi;
use App\Support\Money;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

/**
 * Record a paid bill: who paid, how much, with which card, plus photos of
 * the bill or receipt.
 *
 * Pickers in the native UI kit work on labels, so each select holds a label
 * and is mapped back to its id on save.
 */
class NewBill extends NativeComponent
{
    use RequiresSignIn, UploadsMedia;

    protected const string NoCard = 'Not recorded';

    public string $payee = '';

    public string $amount = '';

    public string $category = '';

    public string $paidOn = '';

    public string $paidBy = '';

    public string $paymentMethod = self::NoCard;

    public string $notes = '';

    /** Only the person who paid or entered it sees it, and it isn't split. */
    public bool $isPrivate = false;

    /** @var list<array{key: string, path: string, mime_type: string, name: string, status: string, attachment_id: string|null}> */
    public array $media = [];

    /** @var array<string, mixed> */
    public array $options = [];

    /** @var array<string, string> */
    public array $fieldErrors = [];

    public bool $saving = false;

    public ?string $error = null;

    public function mount(LivemaasApi $api, ApiSession $session): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        $this->paidOn = now()->toDateString();
        $this->loadOptions($api);

        $me = collect($this->options['members'] ?? [])->firstWhere('id', $session->userId());
        $this->paidBy = $me['name'] ?? ($this->options['members'][0]['name'] ?? '');
        $this->category = $this->options['categories'][0]['label'] ?? '';
    }

    /**
     * Back from adding a card: pick it up in the card list.
     */
    public function onResume(): void
    {
        $this->loadOptions(app(LivemaasApi::class));
    }

    public function addCard(): void
    {
        $this->navigate('/payment-methods/new');
    }

    /**
     * @return list<string>
     */
    public function categoryLabels(): array
    {
        return array_column($this->options['categories'] ?? [], 'label');
    }

    /**
     * @return list<string>
     */
    public function memberNames(): array
    {
        return array_column($this->options['members'] ?? [], 'name');
    }

    /**
     * @return list<string>
     */
    public function cardNames(): array
    {
        return [self::NoCard, ...array_column($this->options['payment_methods'] ?? [], 'display_name')];
    }

    public function save(LivemaasApi $api): void
    {
        if ($this->saving || $this->isUploading()) {
            return;
        }

        $this->error = null;
        $this->fieldErrors = [];

        $cents = Money::parse($this->amount);

        if (trim($this->payee) === '') {
            $this->fieldErrors['payee'] = 'Who was the bill from?';
        }

        if ($cents === null) {
            $this->fieldErrors['amount'] = 'Enter an amount like 142.37.';
        }

        if ($this->hasFailedUploads()) {
            $this->error = 'Remove or retry the failed upload first.';
        }

        if ($this->fieldErrors !== [] || $this->error !== null) {
            return;
        }

        $this->saving = true;

        try {
            $this->persist($api, [
                'payee' => trim($this->payee),
                'amount_cents' => $cents,
                'category' => $this->idFor('categories', 'label', $this->category, key: 'value'),
                'paid_on' => $this->paidOn,
                'paid_by_id' => $this->idFor('members', 'name', $this->paidBy),
                'payment_method_id' => $this->paymentMethod === self::NoCard
                    ? null
                    : $this->idFor('payment_methods', 'display_name', $this->paymentMethod),
                'notes' => trim($this->notes) === '' ? null : trim($this->notes),
                'is_private' => $this->isPrivate,
                'attachment_ids' => $this->attachmentIds(),
            ]);
        } catch (ApiException $exception) {
            $this->error = collect($exception->errors)->flatten()->first() ?? $exception->getMessage();
            $this->saving = false;

            return;
        }

        $this->back();
    }

    public function navTitle(): string
    {
        return 'Add a bill';
    }

    /**
     * @param  array<string, mixed>  $bill
     */
    protected function persist(LivemaasApi $api, array $bill): void
    {
        $api->createBill($bill);
    }

    public function render(): View
    {
        return view('native.new-bill');
    }

    protected function loadOptions(LivemaasApi $api): void
    {
        try {
            $this->options = $api->billOptions();
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    private function idFor(string $list, string $field, string $label, string $key = 'id'): mixed
    {
        return collect($this->options[$list] ?? [])->firstWhere($field, $label)[$key] ?? null;
    }
}
