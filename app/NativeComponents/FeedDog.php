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
 * Log a bowl. Built to be two taps at mealtime: the meal is guessed from the
 * time of day and the amount defaults to what this dog got last time.
 */
class FeedDog extends NativeComponent
{
    use RequiresSignIn;

    private const string NoFood = 'Not recorded';

    /** Scoop sizes offered as one-tap buttons. */
    public const array Presets = [0.5, 1.0, 1.5, 2.0];

    public int $dogId = 0;

    public string $dogName = '';

    public string $meal = '';

    public string $served = '';

    public string $leftover = '';

    public string $food = self::NoFood;

    public string $notes = '';

    /** Feedings post to the house timeline unless switched off. */
    public bool $shareToTimeline = true;

    /** @var array{meals: list<array{value: string, label: string}>, foods: list<array<string, mixed>>} */
    public array $options = ['meals' => [], 'foods' => []];

    /** @var array<string, string> */
    public array $fieldErrors = [];

    public bool $saving = false;

    public ?string $error = null;

    public function mount(LivemaasApi $api, int $id): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        $this->dogId = $id;

        try {
            $dog = $api->dog($id);
            $this->options = $api->dogOptions();
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->dogName = $dog['name'];
        $this->meal = $this->guessMeal();

        $last = $dog['today']['last_feeding'];
        if ($last !== null) {
            $this->served = Cups::format($last['served_cups'], withUnit: false);
            $this->food = $last['food']['display_name'] ?? $this->food;
        }

        if ($this->food === self::NoFood && $this->options['foods'] !== []) {
            $this->food = $this->options['foods'][0]['display_name'];
        }
    }

    public function usePreset(float $cups): void
    {
        $this->served = Cups::format($cups, withUnit: false);
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

    public function presetLabel(float $cups): string
    {
        return Cups::format($cups, withUnit: false);
    }

    public function isPresetSelected(float $cups): bool
    {
        return Cups::parse($this->served) === round($cups, 2);
    }

    public function save(LivemaasApi $api): void
    {
        if ($this->saving) {
            return;
        }

        $this->fieldErrors = [];
        $this->error = null;

        $served = Cups::parse($this->served);
        $leftover = trim($this->leftover) === '' ? 0.0 : Cups::parse($this->leftover);

        if ($served === null || $served <= 0) {
            $this->fieldErrors['served'] = 'How much went in the bowl? e.g. 1½ or 1.5';
        }

        if ($leftover === null || ($served !== null && $leftover > $served)) {
            $this->fieldErrors['leftover'] = 'Leftovers can\'t be more than what was served.';
        }

        if ($this->fieldErrors !== []) {
            return;
        }

        $this->saving = true;

        try {
            $api->logFeeding($this->dogId, [
                'meal' => collect($this->options['meals'])->firstWhere('label', $this->meal)['value'] ?? 'snack',
                'served_cups' => $served,
                'leftover_cups' => $leftover,
                'dog_food_id' => collect($this->options['foods'])->firstWhere('display_name', $this->food)['id'] ?? null,
                'fed_at' => now()->toIso8601String(),
                'notes' => trim($this->notes) === '' ? null : trim($this->notes),
                'share_to_timeline' => $this->shareToTimeline,
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
        return $this->dogName === '' ? 'Feed' : "Feed {$this->dogName}";
    }

    public function render(): View
    {
        return view('native.feed-dog');
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
