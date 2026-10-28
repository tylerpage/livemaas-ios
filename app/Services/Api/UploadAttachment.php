<?php

declare(strict_types=1);

namespace App\Services\Api;

use Native\Mobile\AsyncTask;

/**
 * Uploads one picked photo or video on a background PHP thread, so the
 * compose screen stays responsive while a large video goes up.
 */
class UploadAttachment extends AsyncTask
{
    /**
     * @return array<string, mixed> The confirmed attachment from the API.
     */
    public function handle(string $path, string $mimeType): array
    {
        return app(LivemaasApi::class)->uploadAttachment($path, $mimeType);
    }
}
