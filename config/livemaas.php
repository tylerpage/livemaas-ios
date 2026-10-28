<?php

declare(strict_types=1);

return [

    // The Laravel Cloud API this app talks to. Everything the household
    // records lives there; the phone keeps only the signed-in token.
    'api_url' => rtrim((string) env('LIVEMAAS_API_URL', 'http://127.0.0.1:8000'), '/').'/api/v1',

    'api_timeout_seconds' => 20,

    // Videos go straight from the phone to the bucket; allow for a slow uplink.
    'upload_timeout_seconds' => 600,

];
