<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Facades\Browser;

/**
 * One utility: which card pays it, the account number, and how to sign in.
 * The password stays masked until Show is tapped; long-press any value to copy it.
 */
class UtilityDetail extends NativeComponent
{
    use RequiresSignIn;

    /** @var array<string, mixed>|null */
    public ?array $utility = null;

    public bool $showingPassword = false;

    public bool $confirmingDelete = false;

    public ?string $error = null;

    public int $utilityId = 0;

    public function mount(LivemaasApi $api, int $id): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        $this->utilityId = $id;
        $this->refresh($api);
    }

    /**
     * Back from editing: show the saved changes, password hidden again.
     */
    public function onResume(): void
    {
        $this->showingPassword = false;
        $this->refresh(app(LivemaasApi::class));
    }

    public function refresh(LivemaasApi $api): void
    {
        try {
            $this->utility = $api->utility($this->utilityId);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function togglePassword(): void
    {
        $this->showingPassword = ! $this->showingPassword;
    }

    public function openWebsite(): void
    {
        if (($this->utility['website'] ?? null) !== null) {
            Browser::inApp($this->utility['website']);
        }
    }

    public function websiteHost(): string
    {
        return (string) preg_replace('#^https?://#i', '', (string) ($this->utility['website'] ?? ''));
    }

    public function hasSignIn(): bool
    {
        return collect(['website', 'username', 'password', 'login_notes'])
            ->contains(fn (string $field): bool => filled($this->utility[$field] ?? null));
    }

    public function edit(): void
    {
        $this->navigate("/utilities/{$this->utilityId}/edit");
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
            $api->deleteUtility($this->utilityId);
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
            $this->confirmingDelete = false;

            return;
        }

        $this->back();
    }

    public function navTitle(): string
    {
        return $this->utility['name'] ?? 'Utility';
    }

    public function render(): View
    {
        return view('native.utility-detail');
    }
}
