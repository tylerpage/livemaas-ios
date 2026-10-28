<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use App\Support\Cups;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

/**
 * Feed every dog at once: one meal and food, an amount per dog (pre-filled
 * from each dog's last bowl), saved together as a single timeline post.
 */
class FeedDogs extends NativeComponent
{
    use RequiresSignIn;

    private const string NoFood = 'Not recorded';

    /** @var list<array<string, mixed>> */
    public array $dogs = [];

    /**
     * Cups served, as typed, keyed by dog id. Bound per dog with
     * `native:model="servings.{id}"`.
     *
     * @var array<int|string, string>
     */
    public array $servings = [];

    /**
     * Dogs included in this meal, keyed by dog id. Untick one that is
     * skipping a meal.
     *
     * @var array<int|string, bool>
     */
    public array $included = [];

    public string $meal = '';

    public string $food = self::NoFood;

    public string $notes = '';

    /** Feedings post to the house timeline unless switched off. */
    public bool $shareToTimeline = true;

    /** @var array{meals: list<array{value: string, label: string}>, foods: list<array<string, mixed>>} */
    public array $options = ['meals' => [], 'foods' => []];

    /** @var array<int|string, string> */
    public array $fieldErrors = [];

    public bool $saving = false;

    public ?string $error = null;

    public function mount(LivemaasApi $api): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        try {
            $this->dogs = $api->dogs();
            $this->options = $api->dogOptions();
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->meal = $this->guessMeal();

        foreach ($this->dogs as $dog) {
            $last = $dog['today']['last_feeding'];
            $this->servings[$dog['id']] = $last === null ? '' : Cups::format($last['served_cups'], withUnit: false);
            $this->included[$dog['id']] = true;

            if ($this->food === self::NoFood && isset($last['food']['display_name'])) {
                $this->food = $last['food']['display_name'];
            }
        }

        if ($this->food === self::NoFood && $this->options['foods'] !== []) {
            $this->food = $this->options['foods'][0]['display_name'];
        }
    }

    public function usePreset(int $dogId, float $cups): void
    {
        $this->servings[$dogId] = Cups::format($cups, withUnit: false);
        unset($this->fieldErrors[$dogId]);
    }

    public function presetLabel(float $cups): string
    {
        return Cups::format($cups, withUnit: false);
    }

    public function isPresetSelected(int $dogId, float $cups): bool
    {
        return Cups::parse($this->servings[$dogId] ?? '') === round($cups, 2);
    }

    /**
     * @return list<string>
     */
    public function mealLabels(): array
    {
        return array_column($this->options['meals'], 'label');
    }

    /**
     * @return list<string>
     */
    public function foodNames(): array
    {
        return [...array_column($this->options['foods'], 'display_name'), self::NoFood];
    }

    public function save(LivemaasApi $api): void
    {
        if ($this->saving) {
            return;
        }

        $this->fieldErrors = [];
        $this->error = null;
        $bowls = [];

        foreach ($this->dogs as $dog) {
            if (! ($this->included[$dog['id']] ?? false)) {
                continue;
            }

            $served = Cups::parse($this->servings[$dog['id']] ?? '');

            if ($served === null || $served <= 0) {
                $this->fieldErrors[$dog['id']] = "How much did {$dog['name']} get?";

                continue;
            }

            $bowls[] = ['dog_id' => $dog['id'], 'served_cups' => $served];
        }

        if ($this->fieldErrors !== []) {
            return;
        }

        if ($bowls === []) {
            $this->error = 'Pick at least one dog to feed.';

            return;
        }

        $this->saving = true;

        try {
            $api->feedDogs([
                'meal' => collect($this->options['meals'])->firstWhere('label', $this->meal)['value'] ?? 'snack',
                'dog_food_id' => collect($this->options['foods'])->firstWhere('display_name', $this->food)['id'] ?? null,
                'fed_at' => now()->toIso8601String(),
                'notes' => trim($this->notes) === '' ? null : trim($this->notes),
                'share_to_timeline' => $this->shareToTimeline,
                'bowls' => $bowls,
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
        return count($this->dogs) === 2 ? 'Feed both' : 'Feed all';
    }

    public function render(): View
    {
        return view('native.feed-dogs');
    }

    private function guessMeal(): string
    {
        $hour = (int) now()->format('G');

        $value = match (true) {
            $hour < 11 => 'breakfast',
            $hour < 15 => 'lunch',
            default => 'dinner',
        };

        return collect($this->options['meals'])->firstWhere('value', $value)['label'] ?? ($this->options['meals'][0]['label'] ?? '');
    }
}
