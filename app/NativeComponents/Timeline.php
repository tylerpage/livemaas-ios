<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Timeline extends NativeComponent
{
    use RequiresSignIn;

    /** @var list<array<string, mixed>> */
    public array $posts = [];

    public ?string $nextCursor = null;

    public bool $loaded = false;

    public ?string $error = null;

    public function mount(LivemaasApi $api): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        $this->refresh($api);
    }

    /**
     * Coming back from the compose screen: pick up the new post.
     */
    public function onResume(): void
    {
        $this->refresh(app(LivemaasApi::class));
    }

    public function refresh(LivemaasApi $api): void
    {
        $this->load($api, cursor: null);
    }

    public function loadMore(LivemaasApi $api): void
    {
        if ($this->nextCursor !== null) {
            $this->load($api, cursor: $this->nextCursor);
        }
    }

    public function compose(): void
    {
        $this->navigate('/posts/new');
    }

    public function postDeleted(int $postId): void
    {
        $this->posts = array_values(array_filter($this->posts, fn (array $post): bool => $post['id'] !== $postId));
    }

    public function navTitle(): string
    {
        return 'Timeline';
    }

    public function render(): View
    {
        return view('native.timeline');
    }

    private function load(LivemaasApi $api, ?string $cursor): void
    {
        $this->error = null;

        try {
            $page = $api->posts($cursor);
        } catch (ApiException $exception) {
            if ($exception->isUnauthenticated()) {
                $this->replace('/login');

                return;
            }

            $this->error = $exception->getMessage();

            return;
        } finally {
            $this->loaded = true;
        }

        $this->posts = $cursor === null ? $page['posts'] : [...$this->posts, ...$page['posts']];
        $this->nextCursor = $page['next_cursor'];
    }
}
