<?php

namespace App\Services;

class LogService
{
    private static array $levels = [
        'DEBUG' => 100,
        'INFO' => 200,
        'WARNING' => 300,
        'ERROR' => 400,
        'CRITICAL' => 500,
    ];

    public static function debug(string $message, array $context = []): void
    {
        self::log('DEBUG', $message, $context, 'app');
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context, 'app');
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context, 'app');
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context, 'error');
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log('CRITICAL', $message, $context, 'error');
    }

    public static function security(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context, 'security');
    }

    public static function audit(string $action, array $context = []): void
    {
        $context['action'] = $action;
        self::log('INFO', $action, $context, 'audit');
    }

    private static function log(string $level, string $message, array $context, string $channel): void
    {
        $configLevel = config('logging.level', 'debug');
        $configLevelValue = self::$levels[strtoupper($configLevel)] ?? 100;
        $currentLevelValue = self::$levels[$level] ?? 100;

        if ($currentLevelValue < $configLevelValue) {
            return;
        }

        $logPath = config('logging.path');
        $channelFile = config("logging.channels.{$channel}", "{$channel}.log");
        $filepath = $logPath . '/' . $channelFile;

        if (!is_dir($logPath)) {
            mkdir($logPath, 0775, true);
        }

        self::rotateIfNeeded($filepath);

        $timestamp = date('Y-m-d H:i:s');
        $contextJson = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        
        $logLine = sprintf(
            "[%s] %s: %s %s\n",
            $timestamp,
            $level,
            $message,
            $contextJson
        );

        file_put_contents($filepath, $logLine, FILE_APPEND | LOCK_EX);
    }

    private static function rotateIfNeeded(string $filepath): void
    {
        if (!file_exists($filepath)) {
            return;
        }

        $config = config('logging.rotation');
        
        if (!$config['enabled']) {
            return;
        }

        $maxSize = $config['max_size'] ?? 10485760;
        $maxFiles = $config['max_files'] ?? 30;

        if (filesize($filepath) >= $maxSize) {
            $directory = dirname($filepath);
            $filename = basename($filepath);
            $rotatedFile = $directory . '/' . date('Y-m-d_His') . '_' . $filename;
            
            rename($filepath, $rotatedFile);

            self::cleanOldLogs($directory, $filename, $maxFiles);
        }
    }

    private static function cleanOldLogs(string $directory, string $baseFilename, int $maxFiles): void
    {
        $pattern = $directory . '/*_' . $baseFilename;
        $files = glob($pattern);

        if (count($files) <= $maxFiles) {
            return;
        }

        usort($files, function($a, $b) {
            return filemtime($a) <=> filemtime($b);
        });

        $filesToDelete = array_slice($files, 0, count($files) - $maxFiles);
        
        foreach ($filesToDelete as $file) {
            unlink($file);
        }
    }

    public static function logException(\Throwable $exception): void
    {
        self::error('Exception caught', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
