<?php

namespace App\Services;

use App\Core\Session;

class FileService
{
    private array $config;

    public function __construct()
    {
        $this->config = config('security.upload');
    }

    public function upload(array $file, int $empresaId, string $subFolder = ''): array
    {
        $this->validateFile($file);
        
        $this->scanMalware($file['tmp_name']);
        
        $extension = $this->getExtension($file['name']);
        $hash = hash_file('sha256', $file['tmp_name']);
        $filename = $hash . '.' . $extension;
        
        $destinationPath = $this->buildPath($empresaId, $subFolder);
        
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0775, true);
        }

        $fullPath = $destinationPath . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            LogService::error('Failed to move uploaded file', [
                'file' => $file['name'],
                'destination' => $fullPath,
            ]);
            throw new \RuntimeException('Error al guardar el archivo');
        }

        chmod($fullPath, 0644);

        LogService::info('File uploaded successfully', [
            'empresa_id' => $empresaId,
            'filename' => $filename,
            'size' => $file['size'],
            'type' => $file['type'],
            'user_id' => Session::get('user_id'),
        ]);

        return [
            'filename' => $filename,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'mime_type' => $this->getRealMimeType($fullPath),
            'hash' => $hash,
            'path' => $this->getRelativePath($fullPath),
        ];
    }

    private function validateFile(array $file): void
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \InvalidArgumentException('Archivo no válido');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($this->getUploadError($file['error']));
        }

        if ($file['size'] > $this->config['max_size']) {
            $maxMB = round($this->config['max_size'] / 1048576, 2);
            throw new \InvalidArgumentException("El archivo excede el tamaño máximo permitido de {$maxMB}MB");
        }

        $extension = $this->getExtension($file['name']);
        
        if (!in_array($extension, $this->config['allowed_types'])) {
            throw new \InvalidArgumentException('Tipo de archivo no permitido');
        }

        if ($this->config['use_finfo']) {
            $realMimeType = $this->getRealMimeType($file['tmp_name']);
            $allowedMimes = $this->config['allowed_mimes'][$extension] ?? [];
            
            if (!in_array($realMimeType, $allowedMimes)) {
                LogService::warning('MIME type mismatch detected', [
                    'filename' => $file['name'],
                    'expected' => $allowedMimes,
                    'actual' => $realMimeType,
                    'user_id' => Session::get('user_id'),
                ]);
                throw new \InvalidArgumentException('El contenido del archivo no coincide con su extensión');
            }
        }
    }

    private function scanMalware(string $filepath): void
    {
        if (!$this->config['scan_malware']) {
            return;
        }

        $content = file_get_contents($filepath);
        $patterns = $this->config['malware_patterns'];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                LogService::critical('Malware pattern detected in upload', [
                    'pattern' => $pattern,
                    'user_id' => Session::get('user_id'),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ]);
                
                unlink($filepath);
                throw new \RuntimeException('Archivo rechazado por razones de seguridad');
            }
        }
    }

    private function buildPath(int $empresaId, string $subFolder): string
    {
        $basePath = rtrim($this->config['path'], '/');
        
        if ($this->config['prevent_path_traversal']) {
            $subFolder = str_replace(['..', '/', '\\'], '', $subFolder);
        }

        if ($this->config['organize_by_date']) {
            $year = date('Y');
            $month = date('m');
            return "{$basePath}/{$empresaId}/{$year}/{$month}" . ($subFolder ? "/{$subFolder}" : '');
        }

        return "{$basePath}/{$empresaId}" . ($subFolder ? "/{$subFolder}" : '');
    }

    private function getExtension(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if ($this->config['sanitize_filename']) {
            $extension = preg_replace('/[^a-z0-9]/', '', $extension);
        }

        return $extension;
    }

    private function getRealMimeType(string $filepath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        
        return $mimeType ?: 'application/octet-stream';
    }

    private function getRelativePath(string $fullPath): string
    {
        $basePath = rtrim($this->config['path'], '/');
        return str_replace($basePath, '', $fullPath);
    }

    private function getUploadError(int $errorCode): string
    {
        return match($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo es demasiado grande',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo',
            UPLOAD_ERR_EXTENSION => 'Extensión de PHP detuvo la subida',
            default => 'Error desconocido al subir el archivo',
        };
    }

    public function delete(string $relativePath): bool
    {
        $basePath = rtrim($this->config['path'], '/');
        $fullPath = $basePath . $relativePath;

        if (!file_exists($fullPath)) {
            return false;
        }

        if (unlink($fullPath)) {
            LogService::info('File deleted', [
                'path' => $relativePath,
                'user_id' => Session::get('user_id'),
            ]);
            return true;
        }

        return false;
    }

    public function getFullPath(string $relativePath): string
    {
        $basePath = rtrim($this->config['path'], '/');
        return $basePath . $relativePath;
    }
}
