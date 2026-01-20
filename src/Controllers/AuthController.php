<?php

namespace App\Controllers;

use App\Controllers\Base\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Middleware\RateLimitMiddleware;
use App\Services\LogService;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
            return;
        }

        $content = $this->view('auth.login', [
            'errors' => $this->errors(),
            'old' => $this->old('email')
        ]);

        echo $this->layout('auth', $content, ['title' => 'Iniciar Sesión']);
    }

    public function login(Request $request)
    {
        $rateLimiter = new RateLimitMiddleware('login');
        $rateLimiter->handle($request, function() {});

        $data = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $result = $this->auth->login($data['email'], $data['password']);

        if (!$result['success']) {
            if ($request->wantsJson()) {
                return $this->error($result['error'], 401);
            }

            $this->flashError($result['error']);
            Session::flash('old', ['email' => $data['email']]);
            $this->redirect('/login');
            return;
        }

        if ($request->wantsJson()) {
            return $this->success('Login exitoso', ['user' => $result['user']]);
        }

        $this->flashSuccess('Bienvenido, ' . $result['user']['nombre']);
        $this->redirect('/dashboard');
    }

    public function checkEmail(Request $request)
    {
        $rateLimiter = new RateLimitMiddleware('login');
        $rateLimiter->handle($request, function() {});

        if (!$request->isJson()) {
            return $this->error('Invalid request', 400);
        }

        $json = $request->json();
        
        if (!isset($json['email']) || empty($json['email'])) {
            return $this->error('Email requerido', 422);
        }

        $email = filter_var($json['email'], FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Email inválido', 422);
        }

        $db = \App\Core\Database::getInstance();
        $exists = $db->fetch(
            "SELECT id FROM usuarios WHERE email = ? AND estado = 'activo' LIMIT 1",
            [$email]
        );

        LogService::info('Email check attempt', [
            'email' => $email,
            'exists' => (bool)$exists,
            'ip' => $request->ip()
        ]);

        return $this->json([
            'exists' => (bool)$exists
        ]);
    }

    public function showRegister(Request $request)
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
            return;
        }

        $content = $this->view('auth.register', [
            'errors' => $this->errors(),
            'old' => Session::get('_old', [])
        ]);

        echo $this->layout('auth', $content, ['title' => 'Registro']);
    }

    public function register(Request $request)
    {
        $empresaData = $request->only(['nombre', 'ruc', 'sector', 'telefono', 'email_empresa', 'direccion']);
        $userData = $request->only(['nombre_usuario', 'email', 'password', 'password_confirmation']);

        $result = $this->auth->register($empresaData, $userData);

        if (!$result['success']) {
            if ($request->wantsJson()) {
                return $this->error('Error en registro', 422, $result['errors']);
            }

            Session::flash('errors', $result['errors']);
            Session::flash('old', $request->all());
            $this->redirect('/registro');
            return;
        }

        LogService::info('New company registered', [
            'empresa_id' => $result['user']['empresa_id'],
            'user_id' => $result['user']['id']
        ]);

        if ($request->wantsJson()) {
            return $this->success('Registro exitoso', ['user' => $result['user']]);
        }

        $this->flashSuccess('Cuenta creada exitosamente. Bienvenido ' . $result['user']['nombre']);
        $this->redirect('/dashboard');
    }

    public function logout(Request $request)
    {
        $this->auth->logout();

        if ($request->wantsJson()) {
            return $this->success('Sesión cerrada');
        }

        $this->flashInfo('Sesión cerrada exitosamente');
        $this->redirect('/login');
    }
}
