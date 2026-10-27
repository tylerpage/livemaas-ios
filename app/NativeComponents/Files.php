<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Files extends NativeComponent
{
    use RequiresSignIn;

    public function mount(): void
    {
        $this->redirectIfSignedOut();
    }

    public function navTitle(): string
    {
        return 'Files';
    }

    public function render(): View
    {
        return view('native.files');
    }
}
