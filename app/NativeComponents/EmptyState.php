<?php

declare(strict_types=1);

namespace App\NativeComponents;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

/**
 * Centered message for a screen with nothing to show yet.
 * Mounted as `<native:empty-state title="…" message="…" />`.
 */
class EmptyState extends NativeComponent
{
    public string $title = '';

    public string $message = '';

    public function render(): View
    {
        return view('native.empty-state');
    }
}
