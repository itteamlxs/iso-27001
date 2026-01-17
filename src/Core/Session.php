<?php

namespace App\Core;

class Session
{
    private static bool $started = false;
    private static array $fingerprint = [];

    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        $config = config('session');

        ini_set('session.cookie_httponly', $config['http_only'] ? '1' : '0');
        ini_set('session.cookie_secure', $config['secure'] ? '1' : '0');
        ini_set('session.cookie_samesite', $config['same_site']);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_lifetime', $config['lifetime'] * 60);
        ini_set('session.gc_maxlifetime', $config['gc_maxlifetime']);
        ini_set('session.gc_probability', $config['gc_probability']);
        ini_set('session.gc_divisor', $config['gc_divisor']);
        ini_set('session.save_path', $config['files']);

        session_name($config['cookie']);
        
        if (!is_dir($config['files'])) {
            mkdir($config['files'], 0775, true);
        }

        session_start();
        self::$started = true;

        self::validateFingerprint();
        self::checkAbsoluteTimeout();
        self::maybeRegenerate();
        self::cleanupFlash();
    }

    private static function validateFingerprint(): void
    {
        $fingerprintConfig = config('security.session_fingerprint');
        
        if (!$fingerprintConfig['enabled']) {
            return;
        }

        $currentFingerprint = self::generateFingerprint();

        if (!self::has('_fingerprint')) {
            self::put('_fingerprint', $currentFingerprint);
            return;
        }

        $storedFingerprint = self::get('_fingerprint');

        if ($fingerprintConfig['check_ip'] && $currentFingerprint['ip'] !== $storedFingerprint['ip']) {
            self::invalidate();
            abort(403, 'Session validation failed');
        }

        if ($fingerprintConfig['check_user_agent'] && $currentFingerprint['user_agent'] !== $storedFingerprint['user_agent']) {
            self::invalidate();
            abort(403, 'Session validation failed');
        }
    }

    private static function generateFingerprint(): array
    {
        return [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];
    }

    private static function checkAbsoluteTimeout(): void
    {
        $timeout = config('session.absolute_timeout');
        
        if (!self::has('_created_at')) {
            self::put('_created_at', time());
            return;
        }

        $createdAt = self::get('_created_at');
        
        if (time() - $createdAt > $timeout) {
            self::invalidate();
            abort(403, 'Session expired');
        }
    }

    private static function maybeRegenerate(): void
    {
        $interval = config('session.regenerate_interval');
        
        if (!self::has('_last_regenerate')) {
            self::put('_last_regenerate', time());
            return;
        }

        $lastRegenerate = self::get('_last_regenerate');
        
        if (time() - $lastRegenerate > $interval) {
            self::regenerate();
        }
    }

    public static function regenerate(bool $deleteOld = true): bool
    {
        $result = session_regenerate_id($deleteOld);
        
        if ($result) {
            self::put('_last_regenerate', time());
            self::put('_fingerprint', self::generateFingerprint());
        }

        return $result;
    }

    public static function invalidate(): void
    {
        $_SESSION = [];

        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );

        session_destroy();
        self::$started = false;
    }

    public static function put(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, $default = null)
    {
        return $_SESSION['_flash'][$key] ?? $default;
    }

    private static function cleanupFlash(): void
    {
        if (isset($_SESSION['_flash'])) {
            unset($_SESSION['_flash']);
        }
        
        if (isset($_SESSION['_old'])) {
            unset($_SESSION['_old']);
        }
        
        if (isset($_SESSION['_errors'])) {
            unset($_SESSION['_errors']);
        }
    }

    public static function all(): array
    {
        return $_SESSION ?? [];
    }

    public static function getId(): string
    {
        return session_id();
    }
}
