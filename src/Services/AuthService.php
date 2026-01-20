<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\Empresa;
use App\Core\Session;
use App\Core\Database;
use App\Core\TenantContext;

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
        $tenant = TenantContext::getInstance();
        
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
        
        // Verificar RUC único (sin tenant)
        $empresaModel = new Empresa();
        $existeRuc = $tenant->withoutTenant(function() use ($empresaModel, $empresaData) {
            return $empresaModel->findByRuc($empresaData['ruc']);
        });
        
        if ($existeRuc) {
            return [
                'success' => false,
                'errors' => ['ruc' => ['El RUC ya está registrado']]
            ];
        }
        
        // Verificar email único global (sin tenant)
        $emailExists = $tenant->withoutTenant(function() use ($db, $userData) {
            $result = $db->fetch(
                "SELECT id FROM usuarios WHERE email = ? LIMIT 1",
                [$userData['email']]
            );
            return $result !== null;
        });
        
        if ($emailExists) {
            return [
                'success' => false,
                'errors' => ['email' => ['El email ya está registrado']]
            ];
        }
        
        try {
            $db->beginTransaction();
            
            // 1. Crear empresa
            $empresaId = $tenant->withoutTenant(function() use ($empresaModel, $empresaData) {
                return $empresaModel->create($empresaData);
            });
            
            if (!$empresaId) {
                throw new \Exception('Error al crear empresa');
            }
            
            // 2. Crear usuario admin
            $tenant->setTenant($empresaId);
            
            $userData['empresa_id'] = $empresaId;
            $userData['password_hash'] = password_hash($userData['password'], PASSWORD_ARGON2ID);
            $userData['rol'] = 'admin_empresa';
            $userData['estado'] = 'activo';
            
            unset($userData['password']);
            unset($userData['password_confirmation']);
            
            $usuarioModel = new Usuario();
            $userId = $usuarioModel->create($userData);
            
            if (!$userId) {
                throw new \Exception('Error al crear usuario');
            }
            
            // 3. Crear SOA entries (93 controles)
            $controles = $tenant->withoutTenant(function() use ($db) {
                return $db->fetchAll("SELECT id FROM controles ORDER BY id ASC");
            });
            
            if (count($controles) !== 93) {
                throw new \Exception('Faltan controles base en la BD. Ejecutar seeds.');
            }
            
            $soaStmt = $db->getConnection()->prepare(
                "INSERT INTO soa_entries (empresa_id, control_id, aplicable, estado, created_at, updated_at) 
                 VALUES (?, ?, 1, 'no_implementado', NOW(), NOW())"
            );
            
            foreach ($controles as $control) {
                $soaStmt->execute([$empresaId, $control['id']]);
            }
            
            // 4. Crear empresa_requerimientos (7 requerimientos)
            $requerimientos = $tenant->withoutTenant(function() use ($db) {
                return $db->fetchAll("SELECT id FROM requerimientos_base ORDER BY numero ASC");
            });
            
            if (count($requerimientos) !== 7) {
                throw new \Exception('Faltan requerimientos base en la BD. Ejecutar seeds.');
            }
            
            $reqStmt = $db->getConnection()->prepare(
                "INSERT INTO empresa_requerimientos (empresa_id, requerimiento_id, estado, created_at, updated_at) 
                 VALUES (?, ?, 'pendiente', NOW(), NOW())"
            );
            
            foreach ($requerimientos as $req) {
                $reqStmt->execute([$empresaId, $req['id']]);
            }
            
            $db->commit();
            
            LogService::info('Company registered successfully', [
                'empresa_id' => $empresaId,
                'user_id' => $userId,
                'soa_entries' => count($controles),
                'requerimientos' => count($requerimientos)
            ]);
            
            // Auto-login
            Session::put('user_id', $userId);
            Session::put('empresa_id', $empresaId);
            Session::regenerate();
            
            return [
                'success' => true,
                'empresa_id' => $empresaId,
                'user_id' => $userId
            ];
            
        } catch (\Exception $e) {
            $db->rollback();
            
            LogService::error('Company registration failed', [
                'error' => $e->getMessage(),
                'ruc' => $empresaData['ruc'] ?? null
            ]);
            
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
