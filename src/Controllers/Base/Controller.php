<?php

namespace App\Controllers\Base;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Services\AuthService;

abstract class Controller
{
    protected Request $request;
    protected Response $response;
    protected AuthService $auth;

    public function __construct()
    {
        $this->response = new Response();
        $this->auth = new AuthService();
    }

    protected function view(string $view, array $data = []): string
    {
        extract($data);
        
        ob_start();
        
        $viewPath = __DIR__ . '/../../Views/' . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$view}");
        }
        
        include $viewPath;
        
        return ob_get_clean();
    }

    protected function layout(string $layout, string $content, array $data = []): string
    {
        $data['content'] = $content;
        return $this->view("layouts.{$layout}", $data);
    }

    protected function json(array $data, int $statusCode = 200): Response
    {
        return $this->response->json($data, $statusCode);
    }

    protected function success(string $message, array $data = []): Response
    {
        return $this->json(array_merge([
            'success' => true,
            'message' => $message
        ], $data));
    }

    protected function error(string $message, int $statusCode = 400, array $errors = []): Response
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    protected function redirect(string $path): void
    {
        $this->response->redirect($path);
    }

    protected function back(): void
    {
        $this->response->back();
    }

    protected function validate(Request $request, array $rules): array
    {
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                $this->error('Validation failed', 422, $validator->errors())->send();
                exit;
            }

            Session::flash('errors', $validator->errors());
            Session::flash('old', $request->all());
            $this->back();
            exit;
        }

        return $validator->validated();
    }

    protected function user(): ?array
    {
        return $this->auth->user();
    }

    protected function isAuthenticated(): bool
    {
        return $this->auth->check();
    }

    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            Session::flash('error', 'Debe iniciar sesión');
            $this->redirect('/login');
            exit;
        }
    }

    protected function authorize(string $permission): void
    {
        $user = $this->user();

        if (!$user) {
            abort(401, 'No autenticado');
        }

        $usuarioModel = new \App\Models\Usuario();

        if (!$usuarioModel->hasPermission($user['id'], $permission)) {
            abort(403, 'No tiene permisos para esta acción');
        }
    }

    protected function hasRole(string $rol): bool
    {
        $user = $this->user();
        return $user && $user['rol'] === $rol;
    }

    protected function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    protected function flashSuccess(string $message): void
    {
        Session::flash('success', $message);
    }

    protected function flashError(string $message): void
    {
        Session::flash('error', $message);
    }

    protected function flashWarning(string $message): void
    {
        Session::flash('warning', $message);
    }

    protected function flashInfo(string $message): void
    {
        Session::flash('info', $message);
    }

    protected function old(string $key, $default = '')
    {
        return Session::get('_old')[$key] ?? $default;
    }

    protected function errors(): array
    {
        return Session::get('_errors') ?? [];
    }

    protected function hasError(string $field): bool
    {
        $errors = $this->errors();
        return isset($errors[$field]);
    }

    protected function getError(string $field): ?string
    {
        $errors = $this->errors();
        return $errors[$field][0] ?? null;
    }
}
