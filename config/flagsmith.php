<?php

return [
    'api_key' => env('FLAGSMITH_API_KEY'),
    'host' => env('FLAGSMITH_HOST'),
    'environment_ttl' => env('FLAGSMITH_ENVIRONMENT_TTL') !== null ? (int) env('FLAGSMITH_ENVIRONMENT_TTL') : null,
    'enable_analytics' => (bool) env('FLAGSMITH_ENABLE_ANALYTICS', false),
    'offline_mode' => (bool) env('FLAGSMITH_OFFLINE_MODE', false),
];
