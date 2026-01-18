<?php

namespace App\Controllers;

use App\Controllers\Base\Controller;
use App\Core\Request;
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
            'old' => Session::get('_old', [])
        ]);

        return $this->layout('auth', $content);
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
            Session::put('_old', ['email' => $data['email']]);
            $this->redirect('/login');
            return;
        }

        if ($request->wantsJson()) {
            return $this->success('Login exitoso', ['user' => $result['user']]);
        }

        $this->flashSuccess('Bienvenido, ' . $result['user']['nombre']);
        $this->redirect('/dashboard');
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

        return $this->layout('auth', $content);
    }

    public function register(Request $request)
    {
        $empresaData = $request->only(['nombre', 'ruc', 'sector', 'telefono', 'email_empresa', 'direccion']);
        $userData = $request->only(['nombre_usuario', 'email', 'password', 'password_confirmation']);

        $empresaData['email'] = $empresaData['email_empresa'] ?? null;
        unset($empresaData['email_empresa']);

        $userData['nombre'] = $userData['nombre_usuario'];
        unset($userData['nombre_usuario']);

        $result = $this->auth->register($empresaData, $userData);

        if (!$result['success']) {
            if ($request->wantsJson()) {
                return $this->error('Error en registro', 422, $result['errors']);
            }

            Session::flash('errors', $result['errors']);
            Session::put('_old', $request->all());
            $this->redirect('/registro');
            return;
        }

        LogService::info('New company registered', [
            'empresa_id' => $result['empresa_id'],
            'user_id' => $result['user_id']
        ]);

        if ($request->wantsJson()) {
            return $this->success('Registro exitoso', [
                'empresa_id' => $result['empresa_id'],
                'user_id' => $result['user_id']
            ]);
        }

        $this->flashSuccess('Cuenta creada exitosamente. Por favor inicie sesión.');
        $this->redirect('/login');
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
