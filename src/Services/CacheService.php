<?php

namespace App\Services;

class CacheService
{
    private string $cachePath;
    private int $defaultTtl;

    public function __construct()
    {
        $this->cachePath = config('cache.path');
        $this->defaultTtl = config('cache.ttl', 300);

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0775, true);
        }
    }

    public function get(string $key, $default = null)
    {
        $filepath = $this->getFilepath($key);

        if (!file_exists($filepath)) {
            return $default;
        }

        $content = file_get_contents($filepath);
        $data = unserialize($content);

        if (!is_array($data) || !isset($data['expires_at'], $data['value'])) {
            $this->delete($key);
            return $default;
        }

        if (time() > $data['expires_at']) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->getTtlForKey($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time(),
        ];

        $filepath = $this->getFilepath($key);
        $directory = dirname($filepath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $result = file_put_contents($filepath, serialize($data), LOCK_EX);

        if ($result !== false) {
            chmod($filepath, 0644);
            return true;
        }

        return false;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): bool
    {
        $filepath = $this->getFilepath($key);

        if (file_exists($filepath)) {
            return unlink($filepath);
        }

        return false;
    }

    public function flush(): bool
    {
        $files = glob($this->cachePath . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    public function increment(string $key, int $value = 1): int
    {
        $current = (int) $this->get($key, 0);
        $new = $current + $value;
        $this->set($key, $new);

        return $new;
    }

    public function decrement(string $key, int $value = 1): int
    {
        return $this->increment($key, -$value);
    }

    private function getFilepath(string $key): string
    {
        $hash = md5($key);
        $directory = $this->cachePath . '/' . substr($hash, 0, 2);
        return $directory . '/' . $hash . '.cache';
    }

    private function getTtlForKey(string $key): int
    {
        $keys = config('cache.keys', []);

        foreach ($keys as $pattern => $ttl) {
            if (strpos($key, $pattern) === 0) {
                return $ttl;
            }
        }

        return $this->defaultTtl;
    }

    public function cleanup(): int
    {
        $deleted = 0;
        $files = glob($this->cachePath . '/*/*.cache');

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $content = file_get_contents($file);
            $data = unserialize($content);

            if (!is_array($data) || !isset($data['expires_at'])) {
                unlink($file);
                $deleted++;
                continue;
            }

            if (time() > $data['expires_at']) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
