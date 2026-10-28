<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

/**
 * The cards and accounts bills get paid with. Removing one archives it, so
 * past bills still show which card paid.
 */
class PaymentMethods extends NativeComponent
{
    use RequiresSignIn;

    /** @var list<array<string, mixed>> */
    public array $paymentMethods = [];

    public ?int $confirmingRemoval = null;

    public bool $loaded = false;

    public ?string $error = null;

    public function mount(LivemaasApi $api): void
    {
        if (! $this->redirectIfSignedOut()) {
            $this->load($api);
        }
    }

    public function onResume(): void
    {
        $this->load(app(LivemaasApi::class));
    }

    public function add(): void
    {
        $this->navigate('/payment-methods/new');
    }

    public function askToRemove(int $id): void
    {
        $this->confirmingRemoval = $id;
    }

    public function cancelRemoval(): void
    {
        $this->confirmingRemoval = null;
    }

    public function remove(LivemaasApi $api): void
    {
        try {
            $api->archivePaymentMethod((int) $this->confirmingRemoval);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }

        $this->confirmingRemoval = null;
        $this->load($api);
    }

    public function navTitle(): string
    {
        return 'Cards & accounts';
    }

    public function render(): View
    {
        return view('native.payment-methods');
    }

    private function load(LivemaasApi $api): void
    {
        try {
            $this->paymentMethods = $api->paymentMethods();
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        } finally {
            $this->loaded = true;
        }
    }
}
