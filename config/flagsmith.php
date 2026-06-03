<?php

return [
    'enabled' => (bool) env('FLAGSMITH_ENABLED', true),
    'server_side_environment_key' => env('FLAGSMITH_SERVER_SIDE_ENVIRONMENT_KEY'),
    'api_key' => env('FLAGSMITH_API_KEY', env('FLAGSMITH_SERVER_SIDE_ENVIRONMENT_KEY')),
    'host' => env('FLAGSMITH_HOST'),
    'environment_ttl' => env('FLAGSMITH_ENVIRONMENT_TTL') !== null ? (int) env('FLAGSMITH_ENVIRONMENT_TTL') : null,
    'auto_update_environment' => (bool) env('FLAGSMITH_AUTO_UPDATE_ENVIRONMENT', false),
    'enable_analytics' => (bool) env('FLAGSMITH_ENABLE_ANALYTICS', false),
    'context_provider' => env('FLAGSMITH_CONTEXT_PROVIDER'),
    'offline_mode' => (bool) env('FLAGSMITH_OFFLINE_MODE', false),
    'offline_handler' => env('FLAGSMITH_OFFLINE_HANDLER'),
    'cache' => [
        'store' => env('FLAGSMITH_CACHE_STORE'),
        'prefix' => env('FLAGSMITH_CACHE_PREFIX', 'flagsmith'),
        'ttl' => env('FLAGSMITH_CACHE_TTL') !== null ? (int) env('FLAGSMITH_CACHE_TTL') : null,
    ],
];
