<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use App\Support\Cups;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

/**
 * One bowl: record what was left after the meal, or delete a mistaken entry.
 * Opened from the dog screen, which passes the feeding along.
 */
class FeedingDetail extends NativeComponent
{
    use RequiresSignIn;

    /** @var array<string, mixed> */
    public array $feeding = [];

    public string $dogName = '';

    public string $leftover = '';

    public bool $confirmingDelete = false;

    public bool $saving = false;

    public ?string $error = null;

    public function mount(): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        $this->feeding = $this->data('feeding', []);
        $this->dogName = $this->data('dogName', '');

        if ($this->feeding === []) {
            $this->back();

            return;
        }

        $this->leftover = $this->feeding['leftover_cups'] > 0 ? Cups::format($this->feeding['leftover_cups'], withUnit: false) : '';
    }

    public function cups(float $cups): string
    {
        return Cups::format($cups);
    }

    public function fedAtLabel(): string
    {
        return Carbon::parse($this->feeding['fed_at'])->format('l, M j \a\t g:ia');
    }

    public function save(LivemaasApi $api): void
    {
        $this->error = null;
        $leftover = trim($this->leftover) === '' ? 0.0 : Cups::parse($this->leftover);

        if ($leftover === null || $leftover > $this->feeding['served_cups']) {
            $this->error = 'Leftovers can\'t be more than the '.Cups::format($this->feeding['served_cups']).' served.';

            return;
        }

        $this->saving = true;

        try {
            $api->updateFeeding($this->feeding['id'], ['leftover_cups' => $leftover]);
        } catch (ApiException $exception) {
            $this->error = collect($exception->errors)->flatten()->first() ?? $exception->getMessage();
            $this->saving = false;

            return;
        }

        $this->back();
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
            $api->deleteFeeding($this->feeding['id']);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
            $this->confirmingDelete = false;

            return;
        }

        $this->back();
    }

    public function navTitle(): string
    {
        return $this->feeding['meal_label'] ?? 'Feeding';
    }

    public function render(): View
    {
        return view('native.feeding-detail');
    }
}
