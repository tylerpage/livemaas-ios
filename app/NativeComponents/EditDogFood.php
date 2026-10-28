<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use App\Support\Cups;

/**
 * The Add dog food screen, filled in with an existing one. A new photo
 * replaces the one shown.
 */
class EditDogFood extends NewDogFood
{
    public int $foodId = 0;

    public ?string $currentPhotoUrl = null;

    public function mount(int $id = 0): void
    {
        parent::mount();
        $this->foodId = $id;

        try {
            $food = app(LivemaasApi::class)->dogFood($id);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->brand = $food['brand'];
        $this->name = $food['name'];
        $this->cupsPerBag = $food['cups_per_bag'] === null ? '' : Cups::format((float) $food['cups_per_bag'], withUnit: false);
        $this->currentPhotoUrl = $food['photo_url'] ?? null;
    }

    public function navTitle(): string
    {
        return 'Edit dog food';
    }

    /**
     * @param  array<string, mixed>  $food
     */
    protected function persist(LivemaasApi $api, array $food): void
    {
        $api->updateDogFood($this->foodId, $food);
    }
}
