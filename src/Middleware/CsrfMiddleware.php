<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;
use App\Services\LogService;

class CsrfMiddleware
{
    public function handle(Request $request, callable $next)
    {
        if ($request->method() === 'GET') {
            $this->generateToken();
            return $next();
        }

        if (in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $this->validateToken($request);
        }

        $this->regenerateToken();
        
        return $next();
    }

    private function generateToken(): void
    {
        if (!Session::has('_token')) {
            $length = config('security.csrf.token_length', 32);
            $token = bin2hex(random_bytes($length / 2));
            Session::put('_token', $token);
        }
    }

    private function validateToken(Request $request): void
    {
        $token = $request->input('_token') ?? $request->server['HTTP_X_CSRF_TOKEN'] ?? null;
        $sessionToken = Session::get('_token');

        if (!$token || !$sessionToken) {
            LogService::warning('CSRF token missing', [
                'ip' => $request->ip(),
                'url' => $request->url(),
                'method' => $request->method(),
            ]);
            abort(419, 'CSRF token missing');
        }

        if (!hash_equals($sessionToken, $token)) {
            LogService::warning('CSRF token mismatch', [
                'ip' => $request->ip(),
                'url' => $request->url(),
                'method' => $request->method(),
            ]);
            abort(419, 'CSRF token mismatch');
        }
    }

    private function regenerateToken(): void
    {
        if (config('security.csrf.regenerate_on_use', true)) {
            Session::remove('_token');
            $this->generateToken();
        }
    }
}
