<?php

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $content = '';

    public function __construct()
    {
        $this->setSecurityHeaders();
    }

    private function setSecurityHeaders(): void
    {
        $headers = config('security.headers', []);
        
        foreach ($headers as $key => $value) {
            $this->headers[$key] = $value;
        }
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function json(array $data, int $statusCode = 200): self
    {
        $this->statusCode = $statusCode;
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
        $this->content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        return $this;
    }

    public function html(string $content, int $statusCode = 200): self
    {
        $this->statusCode = $statusCode;
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        $this->content = $content;
        
        return $this;
    }

    public function redirect(string $url, int $statusCode = 302): void
    {
        $this->statusCode = $statusCode;
        $this->headers['Location'] = $url;
        $this->send();
        exit;
    }

    public function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    public function download(string $filepath, string $filename = null): void
    {
        if (!file_exists($filepath)) {
            abort(404, 'File not found');
        }

        $filename = $filename ?: basename($filepath);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filepath);
        finfo_close($finfo);

        $this->headers['Content-Type'] = $mimeType;
        $this->headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        $this->headers['Content-Length'] = filesize($filepath);
        $this->headers['Cache-Control'] = 'no-cache, no-store, must-revalidate';
        $this->headers['Pragma'] = 'no-cache';
        $this->headers['Expires'] = '0';

        $this->sendHeaders();
        readfile($filepath);
        exit;
    }

    public function withErrors(array $errors): self
    {
        $_SESSION['_errors'] = $errors;
        return $this;
    }

    public function with(string $key, $value): self
    {
        $_SESSION['_flash'][$key] = $value;
        return $this;
    }

    public function withInput(): self
    {
        $_SESSION['_old'] = $_POST;
        return $this;
    }

    public function send(): void
    {
        $this->sendHeaders();
        echo $this->content;
    }

    private function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }
    }

    public static function make(string $content = '', int $statusCode = 200): self
    {
        $response = new self();
        return $response->setContent($content)->setStatusCode($statusCode);
    }

    public function __toString(): string
    {
        $this->sendHeaders();
        return $this->content;
    }
}
