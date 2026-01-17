<?php

return [
    'driver' => env('SESSION_DRIVER', 'file'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => env('SESSION_ENCRYPT', true),
    'files' => __DIR__ . '/../storage/sessions',
    'connection' => null,
    'table' => 'sessions',
    'store' => null,
    'lottery' => [2, 100],
    'cookie' => 'iso_session',
    'path' => '/',
    'domain' => null,
    'secure' => env('APP_ENV') !== 'local',
    'http_only' => true,
    'same_site' => 'strict',
    
    'regenerate_on_login' => true,
    'regenerate_interval' => 300,
    'absolute_timeout' => 7200,
    
    'gc_probability' => 1,
    'gc_divisor' => 100,
    'gc_maxlifetime' => 7200,
];
