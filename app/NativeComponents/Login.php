<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Services\Api\ApiException;
use App\Services\Api\ApiSession;
use App\Services\Api\LivemaasApi;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Facades\Browser;

class Login extends NativeComponent
{
    public string $email = '';

    public string $password = '';

    public ?string $error = null;

    public bool $signingIn = false;

    public function mount(): void
    {
        if (app(ApiSession::class)->isSignedIn()) {
            $this->replace('/');
        }
    }

    public function signIn(LivemaasApi $api): void
    {
        $this->error = null;

        if (trim($this->email) === '' || $this->password === '') {
            $this->error = 'Enter your email and password.';

            return;
        }

        $this->signingIn = true;

        try {
            $api->signIn(trim($this->email), $this->password, 'iPhone');
        } catch (ApiException $exception) {
            $this->error = $exception->errors['email'][0] ?? $exception->getMessage();

            return;
        } finally {
            $this->signingIn = false;
            $this->password = '';
        }

        $this->replace('/');
    }

    /**
     * Resetting a password happens on the web app; open it in the in-app
     * browser so the emailed link and the new password both stay there.
     */
    public function forgotPassword(): void
    {
        Browser::inApp(config('livemaas.web_url').'/forgot-password');
    }

    public function render(): View
    {
        return view('native.login');
    }
}
