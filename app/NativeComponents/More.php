<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\Services\Api\ApiSession;
use App\Services\Api\LivemaasApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class More extends NativeComponent
{
    use RequiresSignIn;

    public ?string $userName = null;

    public function mount(ApiSession $session): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        $this->userName = $session->userName();
    }

    public function navTitle(): string
    {
        return 'More';
    }

    public function openCards(): void
    {
        $this->navigate('/payment-methods');
    }

    public function openBillSplit(): void
    {
        $this->navigate('/bill-split');
    }

    public function signOut(LivemaasApi $api): void
    {
        $api->signOut();

        $this->replace('/login');
    }

    public function render(): View
    {
        return view('native.more');
    }
}
