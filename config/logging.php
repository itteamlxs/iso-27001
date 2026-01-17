<?php

return [
    'channel' => env('LOG_CHANNEL', 'file'),
    'level' => env('LOG_LEVEL', 'debug'),
    'path' => env('LOG_PATH', __DIR__ . '/../storage/logs'),
    
    'channels' => [
        'app' => 'app.log',
        'error' => 'error.log',
        'security' => 'security.log',
        'audit' => 'audit.log',
    ],
    
    'rotation' => [
        'enabled' => true,
        'max_files' => 30,
        'max_size' => 10485760,
    ],
    
    'levels' => [
        'DEBUG' => 100,
        'INFO' => 200,
        'WARNING' => 300,
        'ERROR' => 400,
        'CRITICAL' => 500,
    ],
];
