<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\Empresa;
use App\Core\Session;
use App\Core\Database;

class AuthService
{
    public function login(string $email, string $password): array
    {
        $usuarioModel = new Usuario();
        $user = $usuarioModel->findByEmail($email);
        
        if (!$user) {
            return [
                'success' => false,
                'error' => 'Credenciales incorrectas'
            ];
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'error' => 'Credenciales incorrectas'
            ];
        }
        
        Session::put('user_id', $user['id']);
        Session::put('empresa_id', $user['empresa_id']);
        Session::regenerate();
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'rol' => $user['rol'],
                'empresa_id' => $user['empresa_id']
            ]
        ];
    }
    
    public function register(array $empresaData, array $userData): array
    {
        $db = Database::getInstance();
        
        // Validar datos empresa
        $erroresEmpresa = $this->validateEmpresaData($empresaData);
        if (!empty($erroresEmpresa)) {
            return [
                'success' => false,
                'errors' => $erroresEmpresa
            ];
        }
        
        // Validar datos usuario
        $erroresUsuario = $this->validateUserData($userData);
        if (!empty($erroresUsuario)) {
            return [
                'success' => false,
                'errors' => $erroresUsuario
            ];
        }
        
        // Verificar email único
        $usuarioModel = new Usuario();
        if ($usuarioModel->emailExists($userData['email'])) {
            return [
                'success' => false,
                'errors' => ['email' => ['El email ya está registrado']]
            ];
        }
        
        try {
            $db->beginTransaction();
            
            // Crear empresa
            $empresaModel = new Empresa();
            $empresaId = $empresaModel->create($empresaData);
            
            if (!$empresaId) {
                throw new \Exception('Error al crear empresa');
            }
            
            // Crear usuario
            $userData['empresa_id'] = $empresaId;
            $userData['password_hash'] = password_hash($userData['password'], PASSWORD_ARGON2ID);
            $userData['rol'] = 'admin_empresa';
            $userData['estado'] = 'activo';
            
            unset($userData['password']);
            unset($userData['password_confirmation']);
            
            $userId = $usuarioModel->create($userData);
            
            if (!$userId) {
                throw new \Exception('Error al crear usuario');
            }
            
            $db->commit();
            
            // Auto-login
            Session::put('user_id', $userId);
            Session::put('empresa_id', $empresaId);
            Session::regenerate();
            
            return [
                'success' => true,
                'user' => [
                    'id' => $userId,
                    'nombre' => $userData['nombre'],
                    'email' => $userData['email'],
                    'rol' => 'admin_empresa',
                    'empresa_id' => $empresaId
                ]
            ];
            
        } catch (\Exception $e) {
            $db->rollback();
            return [
                'success' => false,
                'errors' => ['general' => [$e->getMessage()]]
            ];
        }
    }
    
    private function validateEmpresaData(array $data): array
    {
        $errors = [];
        
        if (empty($data['nombre'])) {
            $errors['nombre'][] = 'El nombre de la empresa es obligatorio';
        }
        
        if (empty($data['ruc'])) {
            $errors['ruc'][] = 'El RUC es obligatorio';
        } elseif (strlen($data['ruc']) !== 11) {
            $errors['ruc'][] = 'El RUC debe tener 11 dígitos';
        } elseif (!ctype_digit($data['ruc'])) {
            $errors['ruc'][] = 'El RUC debe contener solo números';
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email_empresa'][] = 'Email de empresa inválido';
        }
        
        return $errors;
    }
    
    private function validateUserData(array $data): array
    {
        $errors = [];
        
        if (empty($data['nombre'])) {
            $errors['nombre'][] = 'El nombre es obligatorio';
        } elseif (strlen($data['nombre']) < 3) {
            $errors['nombre'][] = 'El nombre debe tener al menos 3 caracteres';
        }
        
        if (empty($data['email'])) {
            $errors['email'][] = 'El email es obligatorio';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Email inválido';
        }
        
        // Validación password fuerte
        if (empty($data['password'])) {
            $errors['password'][] = 'La contraseña es obligatoria';
        } else {
            $passwordErrors = $this->validateStrongPassword($data['password']);
            if (!empty($passwordErrors)) {
                $errors['password'] = $passwordErrors;
            }
        }
        
        if (empty($data['password_confirmation'])) {
            $errors['password_confirmation'][] = 'Debe confirmar la contraseña';
        } elseif ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'][] = 'Las contraseñas no coinciden';
        }
        
        return $errors;
    }
    
    private function validateStrongPassword(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Debe contener al menos una letra mayúscula';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Debe contener al menos una letra minúscula';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Debe contener al menos un número';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Debe contener al menos un carácter especial (!@#$%^&*)';
        }
        
        return $errors;
    }
    
    public function attempt(array $credentials): bool
    {
        $result = $this->login($credentials['email'], $credentials['password']);
        return $result['success'];
    }
    
    public function user(): ?array
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            return null;
        }
        
        $usuarioModel = new Usuario();
        return $usuarioModel->find($userId);
    }
    
    public function check(): bool
    {
        return Session::has('user_id');
    }
    
    public function logout(): void
    {
        Session::remove('user_id');
        Session::remove('empresa_id');
        Session::invalidate();
    }
    
    public function id(): ?int
    {
        return Session::get('user_id');
    }
}
