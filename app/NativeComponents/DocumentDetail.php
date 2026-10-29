<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Facades\Browser;

class DocumentDetail extends NativeComponent
{
    use RequiresSignIn;

    /** @var array<string, mixed>|null */
    public ?array $document = null;

    public bool $confirmingDelete = false;

    public ?string $error = null;

    public int $documentId = 0;

    public function mount(LivemaasApi $api, int $id): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        $this->documentId = $id;
        $this->refresh($api);
    }

    /**
     * Back from editing: show the saved changes.
     */
    public function onResume(): void
    {
        $this->refresh(app(LivemaasApi::class));
    }

    public function refresh(LivemaasApi $api): void
    {
        try {
            $this->document = $api->document($this->documentId);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function edit(): void
    {
        $this->navigate("/documents/{$this->documentId}/edit");
    }

    /**
     * PDFs and full-size photos open in the in-app browser, which renders
     * PDFs natively and offers the share sheet for saving or printing.
     */
    public function openAttachment(int $index): void
    {
        $url = $this->document['attachments'][$index]['url'] ?? null;

        if ($url !== null) {
            Browser::inApp($url);
        }
    }

    public function fileSize(int $bytes): string
    {
        return match (true) {
            $bytes >= 1_048_576 => round($bytes / 1_048_576, 1).' MB',
            $bytes >= 1024 => round($bytes / 1024).' KB',
            default => $bytes.' B',
        };
    }

    public function addedOn(): string
    {
        return Carbon::parse($this->document['created_at'])->format('F j, Y');
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
            $api->deleteDocument($this->document['id']);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
            $this->confirmingDelete = false;

            return;
        }

        $this->back();
    }

    public function navTitle(): string
    {
        return $this->document['title'] ?? 'File';
    }

    public function render(): View
    {
        return view('native.document-detail');
    }
}
