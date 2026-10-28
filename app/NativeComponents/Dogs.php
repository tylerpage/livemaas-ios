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
 * Each dog's day at a glance — eaten against target, last meal — plus the
 * dog food being fed and when each will run out.
 */
class Dogs extends NativeComponent
{
    use RequiresSignIn;

    /** @var list<array<string, mixed>> */
    public array $dogs = [];

    /** @var list<array<string, mixed>> */
    public array $foods = [];

    public ?int $confirmingFinish = null;

    public bool $loaded = false;

    public ?string $error = null;

    public function mount(LivemaasApi $api): void
    {
        if (! $this->redirectIfSignedOut()) {
            $this->refresh($api);
        }
    }

    public function onResume(): void
    {
        $this->refresh(app(LivemaasApi::class));
    }

    public function refresh(LivemaasApi $api): void
    {
        $this->error = null;

        try {
            $this->dogs = $api->dogs();
            $this->foods = $api->dogFoods();
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

    public function cups(float $cups, bool $withUnit = true): string
    {
        return Cups::format($cups, $withUnit);
    }

    /**
     * Share of today's target eaten, for the progress bar (0–1).
     *
     * @param  array<string, mixed>  $dog
     */
    public function progress(array $dog): float
    {
        $target = (float) ($dog['daily_target_cups'] ?? 0);

        return $target > 0 ? min(1.0, $dog['today']['eaten_cups'] / $target) : 0.0;
    }

    /**
     * @param  array<string, mixed>|null  $feeding
     */
    public function lastFedLabel(?array $feeding): string
    {
        if ($feeding === null) {
            return 'Not fed yet';
        }

        return $feeding['meal_label'].' · '.Carbon::parse($feeding['fed_at'])->diffForHumans().' · '.($feeding['fed_by']['name'] ?? 'someone');
    }

    /**
     * @param  array<string, mixed>  $food
     */
    public function foodProgress(array $food): float
    {
        return $food['cups_per_bag'] ? max(0.0, min(1.0, $food['cups_remaining'] / $food['cups_per_bag'])) : 0.0;
    }

    /**
     * @param  array<string, mixed>  $food
     */
    public function foodLabel(array $food): string
    {
        $parts = [];

        if ($food['cups_remaining'] !== null) {
            $parts[] = Cups::format($food['cups_remaining']).' left';
        }

        if ($food['runs_out_on'] !== null) {
            $parts[] = 'runs out '.Carbon::parse($food['runs_out_on'])->format('M j');
        }

        return $parts === [] ? 'Opened '.Carbon::parse($food['opened_on'])->format('M j') : implode(' · ', $parts);
    }

    public function feed(int $dogId): void
    {
        $this->navigate("/dogs/{$dogId}/feed");
    }

    public function feedAll(): void
    {
        $this->navigate('/dogs/feed-all');
    }

    public function openDog(int $dogId): void
    {
        $this->navigate("/dogs/{$dogId}");
    }

    public function addDog(): void
    {
        $this->navigate('/dogs/new');
    }

    public function addFood(): void
    {
        $this->navigate('/dog-foods/new');
    }

    public function editFood(int $foodId): void
    {
        $this->navigate("/dog-foods/{$foodId}/edit");
    }

    public function askToFinish(int $foodId): void
    {
        $this->confirmingFinish = $foodId;
    }

    public function cancelFinish(): void
    {
        $this->confirmingFinish = null;
    }

    public function finishFood(LivemaasApi $api): void
    {
        try {
            $api->finishDogFood((int) $this->confirmingFinish);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }

        $this->confirmingFinish = null;
        $this->refresh($api);
    }

    public function navTitle(): string
    {
        return 'Dogs';
    }

    public function render(): View
    {
        return view('native.dogs');
    }
}
