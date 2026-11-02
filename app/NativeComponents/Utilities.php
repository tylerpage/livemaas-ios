<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

/**
 * The house's utility accounts. Opening one shows its account number and
 * how to sign in.
 */
class Utilities extends NativeComponent
{
    use RequiresSignIn;

    /** @var list<array<string, mixed>> */
    public array $utilities = [];

    public bool $loaded = false;

    public ?string $error = null;

    public function mount(LivemaasApi $api): void
    {
        if (! $this->redirectIfSignedOut()) {
            $this->load($api);
        }
    }

    /**
     * Back from adding, editing or deleting one.
     */
    public function onResume(): void
    {
        $this->load(app(LivemaasApi::class));
    }

    public function refresh(LivemaasApi $api): void
    {
        $this->load($api);
    }

    public function add(): void
    {
        $this->navigate('/utilities/new');
    }

    public function open(int $id): void
    {
        $this->navigate("/utilities/{$id}");
    }

    public function navTitle(): string
    {
        return 'Utilities';
    }

    public function render(): View
    {
        return view('native.utilities');
    }

    private function load(LivemaasApi $api): void
    {
        try {
            $this->utilities = $api->utilities();
            $this->error = null;
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        } finally {
            $this->loaded = true;
        }
    }
}
