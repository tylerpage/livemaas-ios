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

/**
 * One month of paid bills: the total, who paid how much and on which card,
 * then the bills themselves as a running list.
 */
class Bills extends NativeComponent
{
    use RequiresSignIn;

    /** Month being viewed, as Y-m. */
    public string $month = '';

    /** @var list<array<string, mixed>> */
    public array $bills = [];

    /** @var array<string, mixed>|null */
    public ?array $summary = null;

    public bool $loaded = false;

    public ?string $error = null;

    public function mount(LivemaasApi $api): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        $this->month = now()->format('Y-m');
        $this->refresh($api);
    }

    public function onResume(): void
    {
        $this->refresh(app(LivemaasApi::class));
    }

    public function refresh(LivemaasApi $api): void
    {
        $this->error = null;

        try {
            $this->summary = $api->billSummary($this->month);
            $this->bills = $api->bills($this->month);
        } catch (ApiException $exception) {
            if ($exception->isUnauthenticated()) {
                $this->replace('/login');

                return;
            }

            $this->error = $exception->getMessage();
        } finally {
            $this->loaded = true;
        }
    }

    public function previousMonth(LivemaasApi $api): void
    {
        $this->month = $this->monthStart()->subMonth()->format('Y-m');
        $this->refresh($api);
    }

    public function nextMonth(LivemaasApi $api): void
    {
        if (! $this->isCurrentMonth()) {
            $this->month = $this->monthStart()->addMonth()->format('Y-m');
            $this->refresh($api);
        }
    }

    public function isCurrentMonth(): bool
    {
        return $this->month === now()->format('Y-m');
    }

    public function monthLabel(): string
    {
        return $this->monthStart()->format('F Y');
    }

    public function money(int $cents): string
    {
        return Money::format($cents);
    }

    public function paidOnLabel(string $date): string
    {
        return Carbon::parse($date)->format('M j');
    }

    public function addBill(): void
    {
        $this->navigate('/bills/new');
    }

    public function openBill(int $billId): void
    {
        $this->navigate("/bills/{$billId}");
    }

    public function navTitle(): string
    {
        return 'Bills';
    }

    public function render(): View
    {
        return view('native.bills');
    }

    private function monthStart(): Carbon
    {
        return Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
    }
}
