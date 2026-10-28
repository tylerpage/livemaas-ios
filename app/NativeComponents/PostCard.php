<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Facades\Browser;

/**
 * One house log on the timeline. Mounted as `<native:post-card :post="…" />`.
 */
class PostCard extends NativeComponent
{
    /** @var array<string, mixed> */
    public array $post = [];

    public bool $confirmingDelete = false;

    public ?string $error = null;

    /**
     * There is no native video element in v4's UI kit yet, so videos open
     * in the in-app browser, which plays them with the system player.
     */
    public function openVideo(int $index): void
    {
        $url = $this->post['attachments'][$index]['url'] ?? null;

        if ($url !== null) {
            Browser::inApp($url);
        }
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
            $api->deletePost($this->post['id']);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
            $this->confirmingDelete = false;

            return;
        }

        $this->emit('deleted', $this->post['id']);
    }

    public function postedAgo(): string
    {
        return Carbon::parse($this->post['created_at'])->diffForHumans();
    }

    public function render(): View
    {
        return view('native.post-card');
    }
}
