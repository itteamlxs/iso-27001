<?php

return [
    'driver' => env('CACHE_DRIVER', 'file'),
    'ttl' => env('CACHE_TTL', 300),
    'path' => __DIR__ . '/../storage/cache',
    
    'keys' => [
        'dashboard_metrics' => 300,
        'control_list' => 600,
        'domain_list' => 3600,
        'user_permissions' => 1800,
        'requirements' => 300,
    ],
];
