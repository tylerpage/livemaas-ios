<?php

declare(strict_types=1);

namespace App\NativeComponents\Concerns;

use App\Services\Api\UploadAttachment;
use Illuminate\Support\Str;
use Livemaas\MobileDocumentPicker\Events\DocumentsPicked;
use Livemaas\MobileDocumentPicker\Facades\DocumentPicker;
use Native\Mobile\Attributes\On;
use Native\Mobile\Events\Camera\PhotoTaken;
use Native\Mobile\Events\Camera\VideoRecorded;
use Native\Mobile\Events\Gallery\MediaSelected;
use Native\Mobile\Facades\Camera;
use Throwable;

/**
 * Pick or capture photos and videos and upload each one in the background the
 * moment it is chosen, so by the time the form is filled in the media is
 * usually already in the bucket. Render the list with the
 * `native.partials.media-picker` view and send {@see attachmentIds()} on save.
 *
 * The using component must declare `public array $media = [];` and
 * `public ?string $error = null;`.
 */
trait UploadsMedia
{
    protected int $maxMedia = 20;

    public function pickFromLibrary(): void
    {
        Camera::pickImages('all', multiple: true, max_items: max(1, $this->maxMedia - count($this->media)));
    }

    public function takePhoto(): void
    {
        Camera::getPhoto();
    }

    public function recordVideo(): void
    {
        Camera::recordVideo();
    }

    /**
     * PDFs, photos and videos from the iOS Files app.
     */
    public function pickFiles(): void
    {
        DocumentPicker::pick();
    }

    /**
     * @param  list<array{path: string, name: string, mimeType: string}>  $files
     */
    #[On(DocumentsPicked::class)]
    public function documentsPicked(bool $success, array $files = [], ?string $error = null): void
    {
        if ($error !== null) {
            $this->error = $error;
        }

        foreach ($success ? $files : [] as $file) {
            $this->addMedia($file['path'], $file['mimeType'], $file['name']);
        }
    }

    /**
     * @param  list<array{path: string, mimeType: string}>  $files
     */
    #[On(MediaSelected::class)]
    public function mediaSelected(bool $success, array $files = []): void
    {
        if (! $success) {
            return;
        }

        foreach ($files as $file) {
            $this->addMedia($file['path'], $file['mimeType']);
        }
    }

    #[On(PhotoTaken::class)]
    public function photoTaken(string $path, string $mimeType = 'image/jpeg'): void
    {
        $this->addMedia($path, $mimeType);
    }

    #[On(VideoRecorded::class)]
    public function videoRecorded(string $path, string $mimeType = 'video/mp4'): void
    {
        $this->addMedia($path, $mimeType);
    }

    public function retryUpload(string $key): void
    {
        $index = $this->mediaIndexOf($key);

        if ($index !== null) {
            $this->upload($index);
        }
    }

    public function removeMedia(string $key): void
    {
        $this->media = array_values(array_filter($this->media, fn (array $item): bool => $item['key'] !== $key));
    }

    public function isUploading(): bool
    {
        return collect($this->media)->contains('status', 'uploading');
    }

    public function hasFailedUploads(): bool
    {
        return collect($this->media)->contains('status', 'failed');
    }

    /**
     * @return list<string>
     */
    public function attachmentIds(): array
    {
        return array_values(array_filter(array_column($this->media, 'attachment_id')));
    }

    private function addMedia(string $path, string $mimeType, ?string $name = null): void
    {
        if (count($this->media) >= $this->maxMedia) {
            $this->error = "You can add up to {$this->maxMedia} photos and videos.";

            return;
        }

        $this->media[] = [
            'key' => (string) Str::ulid(),
            'path' => $path,
            'mime_type' => $mimeType,
            'name' => $name ?? basename($path),
            'status' => 'uploading',
            'attachment_id' => null,
        ];

        $this->upload(array_key_last($this->media));
    }

    private function upload(int $index): void
    {
        $key = $this->media[$index]['key'];
        $this->media[$index]['status'] = 'uploading';

        UploadAttachment::dispatch($this->media[$index]['path'], $this->media[$index]['mime_type'])
            ->timeout(config('livemaas.upload_timeout_seconds'))
            ->finished(fn (array $attachment) => $this->uploadFinished($key, $attachment['id']))
            ->failed(fn (Throwable $exception) => $this->uploadFailed($key));
    }

    /**
     * Looked up by key, not index: other items may have been removed while
     * this one was uploading.
     */
    private function uploadFinished(string $key, string $attachmentId): void
    {
        $index = $this->mediaIndexOf($key);

        if ($index !== null) {
            $this->media[$index]['status'] = 'ready';
            $this->media[$index]['attachment_id'] = $attachmentId;
        }
    }

    private function uploadFailed(string $key): void
    {
        $index = $this->mediaIndexOf($key);

        if ($index !== null) {
            $this->media[$index]['status'] = 'failed';
        }
    }

    private function mediaIndexOf(string $key): ?int
    {
        foreach ($this->media as $index => $item) {
            if ($item['key'] === $key) {
                return $index;
            }
        }

        return null;
    }
}
