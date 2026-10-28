<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

/**
 * Each member's share of the household's bills. The Bills tab uses it to
 * show who owes whom each month. Private bills are never split.
 */
class BillSplit extends NativeComponent
{
    use RequiresSignIn;

    /** @var list<array{id: int, name: string, share_percent: float|null}> */
    public array $members = [];

    /**
     * Percent as typed, keyed by user id. Bound per member in the view with
     * `__syncProperty('percents.{id}')`, as on the Feed all screen.
     *
     * @var array<int|string, string>
     */
    public array $percents = [];

    public bool $enabled = false;

    public bool $saving = false;

    public ?string $error = null;

    public function mount(LivemaasApi $api): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        try {
            $split = $api->billSplit();
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->members = $split['members'];
        $this->enabled = $split['enabled'];
        $even = $this->members === [] ? 0 : round(100 / count($this->members), 2);

        foreach ($this->members as $member) {
            $this->percents[$member['id']] = $this->format($member['share_percent'] ?? ($this->enabled ? 0.0 : $even));
        }
    }

    public function total(): float
    {
        return round(array_sum(array_map(fn (string $value): float => (float) $value, $this->percents)), 2);
    }

    public function totalIsRight(): bool
    {
        return abs($this->total() - 100) < 0.001;
    }

    public function save(LivemaasApi $api): void
    {
        if (! $this->totalIsRight()) {
            $this->error = 'The shares need to add up to 100%.';

            return;
        }

        $this->store($api, array_map(fn (array $member): array => [
            'user_id' => $member['id'],
            'percent' => (float) ($this->percents[$member['id']] ?? 0),
        ], $this->members));
    }

    public function stopSplitting(LivemaasApi $api): void
    {
        $this->store($api, []);
    }

    public function format(float $percent): string
    {
        return rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.');
    }

    public function navTitle(): string
    {
        return 'Bill split';
    }

    public function render(): View
    {
        return view('native.bill-split');
    }

    /**
     * @param  list<array{user_id: int, percent: float}>  $shares
     */
    private function store(LivemaasApi $api, array $shares): void
    {
        $this->saving = true;
        $this->error = null;

        try {
            $api->saveBillSplit($shares);
        } catch (ApiException $exception) {
            $this->error = collect($exception->errors)->flatten()->first() ?? $exception->getMessage();
            $this->saving = false;

            return;
        }

        $this->back();
    }
}
