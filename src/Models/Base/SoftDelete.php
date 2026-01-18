<?php

namespace App\Models\Base;

use App\Services\LogService;

trait SoftDelete
{
    protected string $deletedAtColumn = 'estado_gap';
    protected string $deletedValue = 'eliminado';
    protected string $activeValue = 'activo';

    public function softDelete(int $id): bool
    {
        $where = "{$this->primaryKey} = :id";
        $params = ['id' => $id];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $where .= " AND empresa_id = :tenant_id";
            $params['tenant_id'] = $this->tenant->requireTenant();
        }

        $data = [$this->deletedAtColumn => $this->deletedValue];

        if ($this->timestamps) {
            $data['updated_at'] = now();
        }

        $affected = $this->db->update($this->table, $data, $where, $params);

        if ($affected > 0) {
            LogService::audit('Model soft deleted', [
                'table' => $this->table,
                'id' => $id,
                'tenant_id' => $params['tenant_id'] ?? null
            ]);

            $this->cascadeSoftDelete($id);
        }

        return $affected > 0;
    }

    public function restore(int $id): bool
    {
        $where = "{$this->primaryKey} = :id";
        $params = ['id' => $id];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $where .= " AND empresa_id = :tenant_id";
            $params['tenant_id'] = $this->tenant->requireTenant();
        }

        $data = [$this->deletedAtColumn => $this->activeValue];

        if ($this->timestamps) {
            $data['updated_at'] = now();
        }

        $affected = $this->db->update($this->table, $data, $where, $params);

        if ($affected > 0) {
            LogService::audit('Model restored', [
                'table' => $this->table,
                'id' => $id,
                'tenant_id' => $params['tenant_id'] ?? null
            ]);
        }

        return $affected > 0;
    }

    public function findWithTrashed(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $params = [$id];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " LIMIT 1";

        $result = $this->db->fetch($sql, $params);

        return $result ? $this->cast($result) : null;
    }

    public function findOnlyTrashed(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? AND {$this->deletedAtColumn} = ?";
        $params = [$id, $this->deletedValue];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " LIMIT 1";

        $result = $this->db->fetch($sql, $params);

        return $result ? $this->cast($result) : null;
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? AND {$this->deletedAtColumn} = ?";
        $params = [$id, $this->activeValue];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " LIMIT 1";

        $result = $this->db->fetch($sql, $params);

        return $result ? $this->cast($result) : null;
    }

    public function findAll(array $conditions = [], int $limit = 100, int $offset = 0): array
    {
        $where = ["{$this->deletedAtColumn} = ?"];
        $params = [$this->activeValue];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $where[] = "empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        foreach ($conditions as $key => $value) {
            $where[] = "{$key} = ?";
            $params[] = $value;
        }

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where);
        $sql .= " LIMIT {$limit} OFFSET {$offset}";

        $results = $this->db->fetchAll($sql, $params);

        return array_map([$this, 'cast'], $results);
    }

    public function forceDelete(int $id): bool
    {
        $where = "{$this->primaryKey} = :id";
        $params = ['id' => $id];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $where .= " AND empresa_id = :tenant_id";
            $params['tenant_id'] = $this->tenant->requireTenant();
        }

        $affected = $this->db->delete($this->table, $where, $params);

        if ($affected > 0) {
            LogService::audit('Model force deleted', [
                'table' => $this->table,
                'id' => $id,
                'tenant_id' => $params['tenant_id'] ?? null
            ]);
        }

        return $affected > 0;
    }

    protected function cascadeSoftDelete(int $parentId): void
    {
        // Override en modelos específicos para cascada personalizada
    }
}
