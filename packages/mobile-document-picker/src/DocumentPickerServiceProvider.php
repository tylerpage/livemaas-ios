<?php

declare(strict_types=1);

namespace Livemaas\MobileDocumentPicker;

use Illuminate\Support\ServiceProvider;

class DocumentPickerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentPicker::class);
    }
}
