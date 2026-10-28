<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\NativeComponents\Concerns\UploadsMedia;
use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

/**
 * File a document: a title, a category, and the PDFs or photos that make it up.
 */
class NewDocument extends NativeComponent
{
    use RequiresSignIn, UploadsMedia;

    public string $title = '';

    public string $category = '';

    public string $notes = '';

    /** @var list<array{key: string, path: string, mime_type: string, name: string, status: string, attachment_id: string|null}> */
    public array $media = [];

    /** @var list<array{value: string, label: string}> */
    public array $categories = [];

    /** @var array<string, string> */
    public array $fieldErrors = [];

    public bool $saving = false;

    public ?string $error = null;

    public function mount(LivemaasApi $api): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        try {
            $this->categories = $api->documentCategories();
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }

        $this->category = $this->categories[0]['label'] ?? '';
    }

    /**
     * @return list<string>
     */
    public function categoryLabels(): array
    {
        return array_column($this->categories, 'label');
    }

    public function save(LivemaasApi $api): void
    {
        if ($this->saving || $this->isUploading()) {
            return;
        }

        $this->error = null;
        $this->fieldErrors = [];

        if (trim($this->title) === '') {
            $this->fieldErrors['title'] = 'Give it a name, like "Home insurance 2026".';
        }

        if ($this->media === []) {
            $this->error = 'Add at least one file or photo.';
        } elseif ($this->hasFailedUploads()) {
            $this->error = 'Remove or retry the failed upload first.';
        }

        if ($this->fieldErrors !== [] || $this->error !== null) {
            return;
        }

        $this->saving = true;

        try {
            $api->createDocument([
                'title' => trim($this->title),
                'category' => collect($this->categories)->firstWhere('label', $this->category)['value'] ?? 'other',
                'notes' => trim($this->notes) === '' ? null : trim($this->notes),
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
        return 'Add a file';
    }

    public function render(): View
    {
        return view('native.new-document');
    }
}
