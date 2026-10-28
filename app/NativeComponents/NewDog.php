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

class NewDog extends NativeComponent
{
    use RequiresSignIn, UploadsMedia;

    public string $name = '';

    public string $breed = '';

    public ?string $birthday = null;

    public string $dailyTarget = '';

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
        $target = trim($this->dailyTarget) === '' ? null : Cups::parse($this->dailyTarget);

        if (trim($this->name) === '') {
            $this->fieldErrors['name'] = 'What is their name?';
        }

        if (trim($this->dailyTarget) !== '' && $target === null) {
            $this->fieldErrors['dailyTarget'] = 'Cups per day, like 3 or 2½.';
        }

        if ($this->fieldErrors !== []) {
            return;
        }

        $this->saving = true;

        try {
            $api->createDog([
                'name' => trim($this->name),
                'breed' => trim($this->breed) === '' ? null : trim($this->breed),
                'birthday' => $this->birthday,
                'daily_target_cups' => $target,
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
        return 'Add a dog';
    }

    public function render(): View
    {
        return view('native.new-dog');
    }
}
