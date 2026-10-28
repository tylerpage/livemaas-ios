<?php

declare(strict_types=1);

namespace Livemaas\MobileDocumentPicker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Native\Mobile\Events\Contracts\DispatchableFromNative;

/**
 * Fired by the native side when the Files picker closes. Each file is a copy
 * in the app's temp directory, safe to read and upload.
 */
class DocumentsPicked implements DispatchableFromNative
{
    use Dispatchable;

    /**
     * @param  list<array{path: string, name: string, mimeType: string, size: int}>  $files
     */
    public function __construct(
        public bool $success,
        public array $files = [],
        public int $count = 0,
        public bool $cancelled = false,
        public ?string $error = null,
        public ?string $id = null,
    ) {}
}
