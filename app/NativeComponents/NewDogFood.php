<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\NativeComponents\Concerns\UploadsMedia;
use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use App\Support\Cups;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

/**
 * Add a dog food. Its total size in cups lets the app estimate when it runs
 * out; an optional photo makes the right food easy to reorder. EditDogFood
 * reuses this screen for an existing one.
 */
class NewDogFood extends NativeComponent
{
    use RequiresSignIn, UploadsMedia;

    public string $brand = '';

    public string $name = '';

    public string $cupsPerBag = '';

    /** @var list<array{key: string, path: string, mime_type: string, name: string, status: string, attachment_id: string|null}> */
    public array $media = [];

    /** @var array<string, string> */
    public array $fieldErrors = [];

    public bool $saving = false;

    public ?string $error = null;

    public function mount(): void
    {
        $this->maxMedia = 1;
        $this->redirectIfSignedOut();
    }

    public function save(LivemaasApi $api): void
    {
        if ($this->saving || $this->isUploading()) {
            return;
        }

        $this->fieldErrors = [];
        $this->error = null;
        $cups = trim($this->cupsPerBag) === '' ? null : Cups::parse($this->cupsPerBag);

        if (trim($this->brand) === '') {
            $this->fieldErrors['brand'] = 'Which brand?';
        }

        if (trim($this->name) === '') {
            $this->fieldErrors['name'] = 'Which formula, like "Adult Chicken & Rice"?';
        }

        if (trim($this->cupsPerBag) !== '' && ($cups === null || $cups <= 0)) {
            $this->fieldErrors['cupsPerBag'] = 'Roughly how many cups in total?';
        }

        if ($this->hasFailedUploads()) {
            $this->error = 'Remove or retry the photo first.';
        }

        if ($this->fieldErrors !== [] || $this->error !== null) {
            return;
        }

        $this->saving = true;

        try {
            $this->persist($api, [
                'brand' => trim($this->brand),
                'name' => trim($this->name),
                'cups_per_bag' => $cups,
                'attachment_ids' => $this->attachmentIds(),
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
        return 'Add dog food';
    }

    /**
     * @param  array<string, mixed>  $food
     */
    protected function persist(LivemaasApi $api, array $food): void
    {
        $api->createDogFood($food);
    }

    public function render(): View
    {
        return view('native.new-dog-food');
    }
}
