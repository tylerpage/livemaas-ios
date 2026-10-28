<?php

declare(strict_types=1);

namespace Livemaas\MobileDocumentPicker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void pick(bool $multiple = true, ?string $id = null)
 *
 * @see \Livemaas\MobileDocumentPicker\DocumentPicker
 */
class DocumentPicker extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Livemaas\MobileDocumentPicker\DocumentPicker::class;
    }
}
