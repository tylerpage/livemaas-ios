<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Facades\Browser;

class BillDetail extends NativeComponent
{
    use RequiresSignIn;

    /** @var array<string, mixed>|null */
    public ?array $bill = null;

    public bool $confirmingDelete = false;

    public ?string $error = null;

    public function mount(LivemaasApi $api, int $id): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        try {
            $this->bill = $api->bill($id);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function money(int $cents): string
    {
        return Money::format($cents);
    }

    public function paidOnLabel(): string
    {
        return Carbon::parse($this->bill['paid_on'])->format('l, F j, Y');
    }

    /**
     * PDFs and full-size photos open in the in-app browser.
     */
    public function openAttachment(int $index): void
    {
        $url = $this->bill['attachments'][$index]['url'] ?? null;

        if ($url !== null) {
            Browser::inApp($url);
        }
    }

    public function askToDelete(): void
    {
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    public function delete(LivemaasApi $api): void
    {
        try {
            $api->deleteBill($this->bill['id']);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
            $this->confirmingDelete = false;

            return;
        }

        $this->back();
    }

    public function navTitle(): string
    {
        return $this->bill['payee'] ?? 'Bill';
    }

    public function render(): View
    {
        return view('native.bill-detail');
    }
}
