<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

/**
 * Add a utility: what it is, which card pays it, the account number and how
 * to sign in.
 */
class NewUtility extends NativeComponent
{
    use RequiresSignIn;

    protected const string NoCard = 'Not set';

    public string $name = '';

    public string $kind = '';

    public string $paymentMethod = self::NoCard;

    public string $accountNumber = '';

    public string $website = '';

    public string $username = '';

    public string $password = '';

    public string $loginNotes = '';

    /** @var list<array{value: string, label: string}> */
    public array $kinds = [];

    /** @var list<array<string, mixed>> */
    public array $paymentMethods = [];

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
            $options = $api->utilityOptions();
            $this->kinds = $options['kinds'];
            $this->paymentMethods = $options['payment_methods'];
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }

        $this->kind = $this->kinds[0]['label'] ?? '';
    }

    /**
     * @return list<string>
     */
    public function kindLabels(): array
    {
        return array_column($this->kinds, 'label');
    }

    /**
     * @return list<string>
     */
    public function cardNames(): array
    {
        return [self::NoCard, ...array_column($this->paymentMethods, 'display_name')];
    }

    public function save(LivemaasApi $api): void
    {
        if ($this->saving) {
            return;
        }

        $this->error = null;
        $this->fieldErrors = [];

        if (trim($this->name) === '') {
            $this->fieldErrors['name'] = 'Give it a name, like "Xcel Energy".';

            return;
        }

        $this->saving = true;

        try {
            $this->persist($api, [
                'name' => trim($this->name),
                'kind' => collect($this->kinds)->firstWhere('label', $this->kind)['value'] ?? 'other',
                'payment_method_id' => collect($this->paymentMethods)->firstWhere('display_name', $this->paymentMethod)['id'] ?? null,
                'account_number' => $this->blankToNull($this->accountNumber),
                'website' => $this->blankToNull($this->website),
                'username' => $this->blankToNull($this->username),
                // Passwords keep their spaces; only an empty one is cleared.
                'password' => $this->password === '' ? null : $this->password,
                'login_notes' => $this->blankToNull($this->loginNotes),
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
        return 'Add a utility';
    }

    public function render(): View
    {
        return view('native.new-utility');
    }

    /**
     * @param  array<string, mixed>  $utility
     */
    protected function persist(LivemaasApi $api, array $utility): void
    {
        $api->createUtility($utility);
    }

    private function blankToNull(string $value): ?string
    {
        return trim($value) === '' ? null : trim($value);
    }
}
