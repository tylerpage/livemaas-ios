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
 * Compose a house log: text, photos and videos.
 */
class NewPost extends NativeComponent
{
    use RequiresSignIn, UploadsMedia;

    public string $body = '';

    /** @var list<array{key: string, path: string, mime_type: string, name: string, status: string, attachment_id: string|null}> */
    public array $media = [];

    public bool $posting = false;

    public ?string $error = null;

    public function mount(): void
    {
        $this->redirectIfSignedOut();
    }

    public function canPost(): bool
    {
        if ($this->posting || $this->isUploading() || $this->hasFailedUploads()) {
            return false;
        }

        return trim($this->body) !== '' || $this->media !== [];
    }

    public function post(LivemaasApi $api): void
    {
        if (! $this->canPost()) {
            return;
        }

        $this->posting = true;
        $this->error = null;

        try {
            $api->createPost(trim($this->body) === '' ? null : $this->body, $this->attachmentIds());
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
            $this->posting = false;

            return;
        }

        $this->back();
    }

    public function navTitle(): string
    {
        return 'New house log';
    }

    public function render(): View
    {
        return view('native.new-post');
    }
}
