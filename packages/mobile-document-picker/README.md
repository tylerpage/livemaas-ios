# livemaas/mobile-document-picker

Local NativePHP Mobile plugin: opens the iOS Files picker (PDFs, photos, videos) and returns copies the app can upload.

```php
use Livemaas\MobileDocumentPicker\Facades\DocumentPicker;
use Livemaas\MobileDocumentPicker\Events\DocumentsPicked;

DocumentPicker::pick();

#[On(DocumentsPicked::class)]
public function documentsPicked(bool $success, array $files = []): void { /* path, name, mimeType, size */ }
```

iOS only. No extra Info.plist permissions are needed for the Files picker.
