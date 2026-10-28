<?php

declare(strict_types=1);

namespace Livemaas\MobileDocumentPicker;

use Livemaas\MobileDocumentPicker\Events\DocumentsPicked;

class DocumentPicker
{
    /**
     * Open the iOS Files picker. The choice arrives later as a
     * {@see DocumentsPicked} event — listen with `#[On(DocumentsPicked::class)]`.
     */
    public function pick(bool $multiple = true, ?string $id = null): void
    {
        if (function_exists('nativephp_call')) {
            nativephp_call('DocumentPicker.Pick', json_encode(['multiple' => $multiple, 'id' => $id]));
        }
    }
}
