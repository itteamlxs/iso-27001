<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;
use App\Services\LogService;

class AuthMiddleware
{
    public function handle(Request $request, callable $next)
    {
        if (!Session::has('user_id')) {
            LogService::info('Unauthorized access attempt', [
                'ip' => $request->ip(),
                'url' => $request->url(),
                'user_agent' => $request->userAgent(),
            ]);
            
            if ($request->wantsJson()) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
            
            Session::flash('error', 'Debe iniciar sesión para continuar');
            redirect('/login');
        }

        $this->validateUserStatus();
        
        return $next();
    }

    private function validateUserStatus(): void
    {
        $userId = Session::get('user_id');
        
        $db = \App\Core\Database::getInstance();
        $user = $db->fetch(
            "SELECT id, estado FROM usuarios WHERE id = ? LIMIT 1",
            [$userId]
        );

        if (!$user) {
            LogService::warning('User not found in database', [
                'user_id' => $userId,
            ]);
            Session::invalidate();
            redirect('/login');
        }

        if ($user['estado'] !== 'activo') {
            LogService::warning('Inactive user attempted access', [
                'user_id' => $userId,
                'estado' => $user['estado'],
            ]);
            Session::invalidate();
            Session::flash('error', 'Su cuenta ha sido desactivada');
            redirect('/login');
        }
    }
}
