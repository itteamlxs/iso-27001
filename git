<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Core\TenantContext;
use App\Core\Validator;

class AuthService
{
    private Database $db;
    private TenantContext $tenant;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tenant = TenantContext::getInstance();
    }

    public function register(array $empresaData, array $userData): array
    {
        $validator = Validator::make($empresaData, [
            'nombre' => 'required|min:3|max:255',
            'ruc' => 'required|min:11|max:11',
            'email' => 'email',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'errors' => $validator->errors()];
        }

        $validator = Validator::make($userData, [
            'nombre' => 'required|min:3|max:255',
            'email' => 'required|email',
            'password' => 'required|min:' . config('security.password.min_length'),
            'password_confirmation' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'errors' => $validator->errors()];
        }

        if (!$this->validatePasswordStrength($userData['password'])) {
            return [
                'success' => false,
                'errors' => ['password' => ['La contraseña no cumple con los requisitos de seguridad']]
            ];
        }

        if ($this->rucExists($empresaData['ruc'])) {
            return [
                'success' => false,
                'errors' => ['ruc' => ['El RUC ya está registrado']]
            ];
        }

        try {
            $this->db->beginTransaction();

            $empresaId = $this->createEmpresa($empresaData);

            $userId = $this->createUsuario($empresaId, $userData, 'admin_empresa');

            $this->initializeSOA($empresaId);
            $this->initializeRequerimientos($empresaId);

            $this->db->commit();

            LogService::info('User registered successfully', [
                'empresa_id' => $empresaId,
                'user_id' => $userId,
                'email' => $userData['email']
            ]);

            return [
                'success' => true,
                'empresa_id' => $empresaId,
                'user_id' => $userId
            ];

        } catch (\Exception $e) {
            $this->db->rollback();

            LogService::error('Registration failed', [
                'error' => $e->getMessage(),
                'ruc' => $empresaData['ruc']
            ]);

            return [
                'success' => false,
                'errors' => ['general' => ['Error al crear la cuenta. Intente nuevamente.']]
            ];
        }
    }

    public function login(string $email, string $password): array
    {
        $user = $this->db->fetch(
            "SELECT u.*, e.nombre as empresa_nombre 
             FROM usuarios u
             INNER JOIN empresas e ON u.empresa_id = e.id
             WHERE u.email = ? AND u.estado = 'activo'
             LIMIT 1",
            [$email]
        );

        if (!$user) {
            LogService::warning('Login attempt with invalid email', [
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            return [
                'success' => false,
                'error' => 'Credenciales inválidas'
            ];
        }

        if (!password_verify($password, $user['password_hash'])) {
            LogService::warning('Login attempt with invalid password', [
                'email' => $email,
                'user_id' => $user['id'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            return [
                'success' => false,
                'error' => 'Credenciales inválidas'
            ];
        }

        if ($this->needsRehash($user['password_hash'])) {
            $this->rehashPassword($user['id'], $password);
        }

        Session::regenerate(true);

        Session::put('user_id', $user['id']);
        Session::put('empresa_id', $user['empresa_id']);
        Session::put('user_nombre', $user['nombre']);
        Session::put('user_email', $user['email']);
        Session::put('user_rol', $user['rol']);
        Session::put('empresa_nombre', $user['empresa_nombre']);

        $this->tenant->setTenant($user['empresa_id']);

        $this->updateLastAccess($user['id']);

        LogService::info('User logged in', [
            'user_id' => $user['id'],
            'empresa_id' => $user['empresa_id'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'rol' => $user['rol'],
                'empresa_id' => $user['empresa_id'],
                'empresa_nombre' => $user['empresa_nombre']
            ]
        ];
    }

    public function logout(): void
    {
        $userId = Session::get('user_id');
        $empresaId = Session::get('empresa_id');

        LogService::info('User logged out', [
            'user_id' => $userId,
            'empresa_id' => $empresaId
        ]);

        $this->tenant->clearTenant();
        Session::invalidate();
    }

    public function check(): bool
    {
        return Session::has('user_id') && Session::has('empresa_id');
    }

    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        return [
            'id' => Session::get('user_id'),
            'nombre' => Session::get('user_nombre'),
            'email' => Session::get('user_email'),
            'rol' => Session::get('user_rol'),
            'empresa_id' => Session::get('empresa_id'),
            'empresa_nombre' => Session::get('empresa_nombre')
        ];
    }

    private function validatePasswordStrength(string $password): bool
    {
        $config = config('security.password');

        if (strlen($password) < $config['min_length']) {
            return false;
        }

        if ($config['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if ($config['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            return false;
        }

        if ($config['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            return false;
        }

        if ($config['require_special'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    private function hashPassword(string $password): string
    {
        $config = config('security.password');

        return password_hash($password, $config['algorithm'], $config['options']);
    }

    private function needsRehash(string $hash): bool
    {
        $config = config('security.password');

        return password_needs_rehash($hash, $config['algorithm'], $config['options']);
    }

    private function rehashPassword(int $userId, string $password): void
    {
        $newHash = $this->hashPassword($password);

        $this->db->update('usuarios', [
            'password_hash' => $newHash
        ], 'id = :id', ['id' => $userId]);

        LogService::info('Password rehashed', ['user_id' => $userId]);
    }

    private function rucExists(string $ruc): bool
    {
        $result = $this->db->fetch(
            "SELECT id FROM empresas WHERE ruc = ? LIMIT 1",
            [$ruc]
        );

        return $result !== null;
    }

    private function createEmpresa(array $data): int
    {
        return $this->db->insert('empresas', [
            'nombre' => $data['nombre'],
            'ruc' => $data['ruc'],
            'sector' => $data['sector'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'email' => $data['email'] ?? null,
            'direccion' => $data['direccion'] ?? null
        ]);
    }

    private function createUsuario(int $empresaId, array $data, string $rol): int
    {
        return $this->db->insert('usuarios', [
            'empresa_id' => $empresaId,
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'password_hash' => $this->hashPassword($data['password']),
            'rol' => $rol,
            'estado' => 'activo'
        ]);
    }

    private function initializeSOA(int $empresaId): void
    {
        $controles = $this->db->fetchAll("SELECT id FROM controles");

        foreach ($controles as $control) {
            $this->db->insert('soa_entries', [
                'empresa_id' => $empresaId,
                'control_id' => $control['id'],
                'aplicable' => 1,
                'estado' => 'no_implementado'
            ]);
        }

        LogService::info('SOA initialized', [
            'empresa_id' => $empresaId,
            'controles_count' => count($controles)
        ]);
    }

    private function initializeRequerimientos(int $empresaId): void
    {
        $requerimientos = $this->db->fetchAll("SELECT id FROM requerimientos_base");

        foreach ($requerimientos as $req) {
            $this->db->insert('empresa_requerimientos', [
                'empresa_id' => $empresaId,
                'requerimiento_id' => $req['id'],
                'estado' => 'pendiente'
            ]);
        }

        LogService::info('Requerimientos initialized', [
            'empresa_id' => $empresaId,
            'requerimientos_count' => count($requerimientos)
        ]);
    }

    private function updateLastAccess(int $userId): void
    {
        $this->db->update('usuarios', [
            'ultimo_acceso' => now()
        ], 'id = :id', ['id' => $userId]);
    }
}
