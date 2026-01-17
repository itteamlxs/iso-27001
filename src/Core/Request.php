<?php

namespace App\Core;

class Request
{
    private array $params = [];
    private array $query;
    private array $post;
    private array $server;
    private array $files;
    private array $cookies;

    public function __construct()
    {
        $this->query = $this->sanitize($_GET);
        $this->post = $this->sanitize($_POST);
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
    }

    private function sanitize($data): array
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        if (is_string($data)) {
            $data = trim($data);
            $data = stripslashes($data);
        }
        
        return $data;
    }

    public function method(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';
        
        if ($method === 'POST' && $this->has('_method')) {
            $method = strtoupper($this->input('_method'));
            if (in_array($method, ['PUT', 'DELETE', 'PATCH'])) {
                return $method;
            }
        }
        
        return $method;
    }

    public function path(): string
    {
        $path = $this->server['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }
        
        return $path;
    }

    public function url(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    public function fullUrl(): string
    {
        $protocol = $this->isSecure() ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . $this->url();
    }

    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || ($this->server['SERVER_PORT'] ?? 80) == 443;
    }

    public function ip(): string
    {
        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $this->server['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    public function input(string $key, $default = null)
    {
        return $this->all()[$key] ?? $default;
    }

    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function only(array $keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $data[$key] = $this->input($key);
            }
        }
        return $data;
    }

    public function except(array $keys): array
    {
        $data = $this->all();
        foreach ($keys as $key) {
            unset($data[$key]);
        }
        return $data;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    public function cookie(string $key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }

    public function isJson(): bool
    {
        return strpos($this->server['CONTENT_TYPE'] ?? '', 'application/json') !== false;
    }

    public function json(): ?array
    {
        if (!$this->isJson()) {
            return null;
        }
        
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    public function isAjax(): bool
    {
        return strtolower($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    public function wantsJson(): bool
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        return strpos($accept, 'application/json') !== false || $this->isAjax();
    }
}
