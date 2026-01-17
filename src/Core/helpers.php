<?php

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false) {
            return $default;
        }
        
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        
        return $value;
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null) {
        static $config = [];
        
        $keys = explode('.', $key);
        $file = array_shift($keys);
        
        if (!isset($config[$file])) {
            $path = __DIR__ . '/../../config/' . $file . '.php';
            if (!file_exists($path)) {
                return $default;
            }
            $config[$file] = require $path;
        }
        
        $value = $config[$file];
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        
        return $value;
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string {
        return __DIR__ . '/../../storage' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string {
        return __DIR__ . '/../../' . ltrim($path, '/');
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string {
        return __DIR__ . '/../../public' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8', true);
    }
}

if (!function_exists('sanitize_input')) {
    function sanitize_input($value) {
        if (is_array($value)) {
            return array_map('sanitize_input', $value);
        }
        
        if (is_string($value)) {
            $value = trim($value);
            $value = stripslashes($value);
        }
        
        return $value;
    }
}

if (!function_exists('generate_token')) {
    function generate_token(int $length = 32): string {
        return bin2hex(random_bytes($length / 2));
    }
}

if (!function_exists('hash_equals_safe')) {
    function hash_equals_safe(string $known, string $user): bool {
        if (!is_string($known) || !is_string($user)) {
            return false;
        }
        return hash_equals($known, $user);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, int $code = 302): void {
        header("Location: {$path}", true, $code);
        exit;
    }
}

if (!function_exists('abort')) {
    function abort(int $code = 404, string $message = ''): void {
        http_response_code($code);
        echo $message ?: "Error {$code}";
        exit;
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = '') {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return $_SESSION['_token'] ?? '';
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        $token = csrf_token();
        return '<input type="hidden" name="_token" value="' . e($token) . '">';
    }
}

if (!function_exists('now')) {
    function now(): string {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('dd')) {
    function dd(...$vars): void {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        exit;
    }
}
