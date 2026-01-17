<?php

return [
    'csrf' => [
        'token_length' => env('CSRF_TOKEN_LENGTH', 32),
        'token_name' => '_token',
        'regenerate_on_use' => true,
    ],
    
    'rate_limit' => [
        'storage' => 'hybrid',
        'attempts' => env('RATE_LIMIT_ATTEMPTS', 5),
        'decay_minutes' => env('RATE_LIMIT_DECAY_MINUTES', 15),
        'limits' => [
            'login' => ['attempts' => 5, 'decay' => 15, 'by' => 'ip+email'],
            'upload' => ['attempts' => 10, 'decay' => 60, 'by' => 'user'],
            'form' => ['attempts' => 20, 'decay' => 5, 'by' => 'user'],
            'api' => ['attempts' => 100, 'decay' => 1, 'by' => 'user'],
            'download' => ['attempts' => 50, 'decay' => 60, 'by' => 'user'],
        ],
        'block_duration' => 900,
        'exponential_backoff' => true,
    ],
    
    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 12),
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_special' => true,
        'algorithm' => PASSWORD_ARGON2ID,
        'options' => [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ],
        'rehash_on_login' => true,
    ],
    
    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' cdn.tailwindcss.com; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'",
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
    ],
    
    'upload' => [
        'max_size' => env('UPLOAD_MAX_SIZE', 10485760),
        'allowed_types' => explode(',', env('UPLOAD_ALLOWED_TYPES', 'pdf,docx,xlsx,png,jpg,jpeg')),
        'path' => env('UPLOAD_PATH', '/var/www/html/public/uploads'),
        'allowed_mimes' => [
            'pdf' => ['application/pdf'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'png' => ['image/png'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
        ],
        'use_finfo' => true,
        'scan_malware' => true,
        'malware_patterns' => [
            '/<\?php/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/base64_decode/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/passthru\s*\(/i',
            '/shell_exec/i',
        ],
        'prevent_path_traversal' => true,
        'sanitize_filename' => true,
        'use_hash_names' => true,
        'organize_by_date' => true,
    ],
    
    'session_fingerprint' => [
        'enabled' => true,
        'check_ip' => true,
        'check_user_agent' => true,
        'regenerate_interval' => 300,
        'absolute_timeout' => 7200,
    ],
    
    'idor_protection' => [
        'enabled' => true,
        'log_attempts' => true,
        'block_after_attempts' => 3,
        'block_duration' => 1800,
    ],
    
    'sql_injection_prevention' => [
        'use_prepared_statements' => true,
        'disable_emulated_prepares' => true,
        'validate_input_types' => true,
    ],
    
    'xss_prevention' => [
        'encode_output' => true,
        'strip_tags_input' => false,
        'allowed_html_tags' => '',
    ],
];
