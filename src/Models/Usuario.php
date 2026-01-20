<?php

namespace App\Models;

use App\Models\Base\Model;

class Usuario extends Model
{
    protected string $table = 'usuarios';
    protected bool $tenantScoped = true;
    
    protected array $fillable = [
        'empresa_id',
        'nombre',
        'email',
        'password_hash',
        'rol',
        'estado'
    ];
    
    protected array $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'empresa_id' => 'int'
    ];

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $params = [$email];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " LIMIT 1";

        $result = $this->db->fetch($sql, $params);

        return $result ? $this->cast($result) : null;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE email = ?";
        $params = [$email];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $sql .= " LIMIT 1";

        $result = $this->db->fetch($sql, $params);

        return $result !== null;
    }

    public function getByRol(string $rol): array
    {
        $conditions = ['rol' => $rol, 'estado' => 'activo'];
        return $this->findAll($conditions);
    }

    public function getActivos(): array
    {
        return $this->findAll(['estado' => 'activo']);
    }

    public function activate(int $id): bool
    {
        return $this->update($id, ['estado' => 'activo']);
    }

    public function deactivate(int $id): bool
    {
        return $this->update($id, ['estado' => 'inactivo']);
    }

    public function block(int $id): bool
    {
        return $this->update($id, ['estado' => 'bloqueado']);
    }

    public function changePassword(int $id, string $newPasswordHash): bool
    {
        return $this->update($id, ['password_hash' => $newPasswordHash]);
    }

    public function updateLastAccess(int $id): bool
    {
        $where = "id = :id";
        $params = ['id' => $id];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $where .= " AND empresa_id = :tenant_id";
            $params['tenant_id'] = $this->tenant->requireTenant();
        }

        return $this->db->update($this->table, [
            'ultimo_acceso' => now()
        ], $where, $params) > 0;
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        $user = $this->find($userId);

        if (!$user) {
            return false;
        }

        $permissions = $this->getRolePermissions($user['rol']);

        return in_array($permission, $permissions);
    }

    public function getRolePermissions(string $rol): array
    {
        return match($rol) {
            'super_admin' => [
                'empresas.view',
                'empresas.create',
                'empresas.edit',
                'empresas.delete',
                'usuarios.view',
                'usuarios.create',
                'usuarios.edit',
                'usuarios.delete',
                'controles.view',
                'controles.edit',
                'gap.view',
                'gap.create',
                'gap.edit',
                'gap.delete',
                'evidencias.view',
                'evidencias.create',
                'evidencias.edit',
                'evidencias.delete',
                'evidencias.validate',
                'requerimientos.view',
            ],
            'admin_empresa' => [
                'usuarios.view',
                'usuarios.create',
                'usuarios.edit',
                'controles.view',
                'controles.edit',
                'gap.view',
                'gap.create',
                'gap.edit',
                'gap.delete',
                'evidencias.view',
                'evidencias.create',
                'evidencias.edit',
                'evidencias.delete',
                'evidencias.validate',
                'requerimientos.view',
            ],
            'auditor' => [
                'controles.view',
                'gap.view',
                'evidencias.view',
                'evidencias.validate',
                'requerimientos.view',
            ],
            'consultor' => [
                'controles.view',
                'gap.view',
                'gap.create',
                'gap.edit',
                'evidencias.view',
                'evidencias.create',
                'requerimientos.view',
            ],
            default => []
        };
    }

    public function getWithEmpresa(int $id): ?array
    {
        $sql = "SELECT u.*, e.nombre as empresa_nombre, e.ruc as empresa_ruc
                FROM {$this->table} u
                INNER JOIN empresas e ON u.empresa_id = e.id
                WHERE u.id = ?";
        $params = [$id];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND u.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " LIMIT 1";

        $result = $this->db->fetch($sql, $params);

        return $result ? $this->cast($result) : null;
    }
}
