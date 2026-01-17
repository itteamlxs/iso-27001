<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;
use App\Services\LogService;

class RateLimitMiddleware
{
    private string $action;
    private array $config;

    public function __construct(string $action = 'default')
    {
        $this->action = $action;
        $this->config = config('security.rate_limit.limits.' . $action, [
            'attempts' => 100,
            'decay' => 1,
            'by' => 'ip'
        ]);
    }

    public function handle(Request $request, callable $next)
    {
        $key = $this->generateKey($request);
        
        if ($this->isBlocked($key)) {
            $this->logRateLimitHit($request, $key);
            $this->sendRateLimitResponse($key);
        }

        $this->incrementAttempts($key);
        
        return $next();
    }

    private function generateKey(Request $request): string
    {
        $identifier = match($this->config['by']) {
            'ip' => $request->ip(),
            'user' => Session::get('user_id', $request->ip()),
            'ip+email' => $request->ip() . ':' . $request->input('email', ''),
            default => $request->ip()
        };

        return 'rate_limit:' . $this->action . ':' . md5($identifier);
    }

    private function isBlocked(string $key): bool
    {
        if (!Session::has($key)) {
            return false;
        }

        $data = Session::get($key);
        $attempts = $data['attempts'] ?? 0;
        $firstAttempt = $data['first_attempt'] ?? time();
        $blockedUntil = $data['blocked_until'] ?? null;

        if ($blockedUntil && time() < $blockedUntil) {
            return true;
        }

        $decayMinutes = $this->config['decay'];
        $decaySeconds = $decayMinutes * 60;

        if (time() - $firstAttempt > $decaySeconds) {
            Session::remove($key);
            return false;
        }

        if ($attempts >= $this->config['attempts']) {
            $blockDuration = $this->calculateBlockDuration($attempts);
            Session::put($key, array_merge($data, [
                'blocked_until' => time() + $blockDuration
            ]));
            return true;
        }

        return false;
    }

    private function incrementAttempts(string $key): void
    {
        $data = Session::get($key, [
            'attempts' => 0,
            'first_attempt' => time()
        ]);

        $data['attempts']++;
        Session::put($key, $data);
    }

    private function calculateBlockDuration(int $attempts): int
    {
        $baseBlockDuration = config('security.rate_limit.block_duration', 900);
        
        if (!config('security.rate_limit.exponential_backoff', true)) {
            return $baseBlockDuration;
        }

        $multiplier = min(pow(2, $attempts - $this->config['attempts']), 32);
        return (int) ($baseBlockDuration * $multiplier);
    }

    private function logRateLimitHit(Request $request, string $key): void
    {
        $data = Session::get($key);
        
        LogService::warning('Rate limit exceeded', [
            'action' => $this->action,
            'ip' => $request->ip(),
            'user_id' => Session::get('user_id'),
            'attempts' => $data['attempts'] ?? 0,
            'blocked_until' => $data['blocked_until'] ?? null,
            'url' => $request->url(),
            'method' => $request->method(),
        ]);
    }

    private function sendRateLimitResponse(string $key): void
    {
        $data = Session::get($key);
        $blockedUntil = $data['blocked_until'] ?? time();
        $remainingSeconds = max(0, $blockedUntil - time());
        $remainingMinutes = ceil($remainingSeconds / 60);

        http_response_code(429);
        header('Retry-After: ' . $remainingSeconds);
        header('X-RateLimit-Limit: ' . $this->config['attempts']);
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . $blockedUntil);

        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Too many requests',
                'retry_after' => $remainingMinutes . ' minutos'
            ]);
        } else {
            echo "Demasiados intentos. Intente nuevamente en {$remainingMinutes} minutos.";
        }
        
        exit;
    }
}
