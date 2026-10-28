<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class NewPaymentMethod extends NativeComponent
{
    use RequiresSignIn;

    private const string Shared = 'Shared';

    public string $nickname = '';

    public string $type = '';

    public string $last4 = '';

    public string $owner = self::Shared;

    /** @var array<string, mixed> */
    public array $options = [];

    /** @var array<string, string> */
    public array $fieldErrors = [];

    public bool $saving = false;

    public ?string $error = null;

    public function mount(LivemaasApi $api): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        try {
            $this->options = $api->billOptions();
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }

        $this->type = $this->options['payment_method_types'][0]['label'] ?? '';
    }

    /**
     * @return list<string>
     */
    public function typeLabels(): array
    {
        return array_column($this->options['payment_method_types'] ?? [], 'label');
    }

    /**
     * @return list<string>
     */
    public function ownerNames(): array
    {
        return [self::Shared, ...array_column($this->options['members'] ?? [], 'name')];
    }

    public function save(LivemaasApi $api): void
    {
        $this->fieldErrors = [];
        $this->error = null;

        if (trim($this->nickname) === '') {
            $this->fieldErrors['nickname'] = 'Give it a name you will recognise, like "Chase Sapphire".';
        }

        if ($this->last4 !== '' && ! preg_match('/^\d{4}$/', $this->last4)) {
            $this->fieldErrors['last4'] = 'Just the last four digits.';
        }

        if ($this->fieldErrors !== []) {
            return;
        }

        $this->saving = true;

        try {
            $api->createPaymentMethod([
                'nickname' => trim($this->nickname),
                'type' => collect($this->options['payment_method_types'] ?? [])->firstWhere('label', $this->type)['value'] ?? 'other',
                'last4' => $this->last4 === '' ? null : $this->last4,
                'owner_id' => collect($this->options['members'] ?? [])->firstWhere('name', $this->owner)['id'] ?? null,
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
        return 'Add a card';
    }

    public function render(): View
    {
        return view('native.new-payment-method');
    }
}
