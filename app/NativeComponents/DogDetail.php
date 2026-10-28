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
 * One dog: the last seven days against target, and recent bowls — tap one
 * to record leftovers or remove a mistaken entry.
 */
class DogDetail extends NativeComponent
{
    use RequiresSignIn;

    public int $dogId = 0;

    /** @var array<string, mixed>|null */
    public ?array $dog = null;

    public ?string $error = null;

    public function mount(LivemaasApi $api, int $id): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        $this->dogId = $id;
        $this->refresh($api);
    }

    public function onResume(): void
    {
        $this->refresh(app(LivemaasApi::class));
    }

    public function refresh(LivemaasApi $api): void
    {
        try {
            $this->dog = $api->dog($this->dogId);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function cups(float $cups, bool $withUnit = true): string
    {
        return Cups::format($cups, $withUnit);
    }

    public function dayLabel(string $date): string
    {
        $day = Carbon::parse($date);

        return $day->isToday() ? 'Today' : $day->format('D');
    }

    /**
     * Bar length for a day, relative to target (or the week's best day when
     * there is no target).
     */
    public function dayProgress(float $eaten): float
    {
        $scale = (float) ($this->dog['daily_target_cups'] ?? 0)
            ?: max(1.0, ...array_column($this->dog['daily'] ?? [], 'eaten_cups'));

        return min(1.0, $eaten / $scale);
    }

    /**
     * @param  array<string, mixed>  $feeding
     */
    public function feedingLabel(array $feeding): string
    {
        return Carbon::parse($feeding['fed_at'])->format('D M j, g:ia').' · '.($feeding['fed_by']['name'] ?? '');
    }

    public function feed(): void
    {
        $this->navigate("/dogs/{$this->dogId}/feed");
    }

    public function openFeeding(int $index): void
    {
        $feeding = $this->dog['recent_feedings'][$index] ?? null;

        if ($feeding !== null) {
            $this->navigate("/feedings/{$feeding['id']}", ['feeding' => $feeding, 'dogName' => $this->dog['name']]);
        }
    }

    public function navTitle(): string
    {
        return $this->dog['name'] ?? 'Dog';
    }

    public function render(): View
    {
        return view('native.dog-detail');
    }
}
