<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;

/**
 * The Add a file screen, filled in with an existing file: change its name,
 * category or notes. Files picked here are added to the ones it has.
 */
class EditDocument extends NewDocument
{
    public int $documentId = 0;

    /** How many files it already has, shown above the picker. */
    public int $existingFiles = 0;

    public function mount(LivemaasApi $api, int $id = 0): void
    {
        parent::mount($api);
        $this->documentId = $id;

        try {
            $document = $api->document($id);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->title = $document['title'];
        $this->category = $document['category_label'];
        $this->notes = $document['notes'] ?? '';
        $this->existingFiles = count($document['attachments'] ?? []);
    }

    public function navTitle(): string
    {
        return 'Edit file';
    }

    protected function needsFiles(): bool
    {
        return false;
    }

    /**
     * @param  array<string, mixed>  $document
     */
    protected function persist(LivemaasApi $api, array $document): void
    {
        $api->updateDocument($this->documentId, $document);
    }
}
