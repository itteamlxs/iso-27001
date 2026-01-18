<?php

namespace App\Models\Base;

use App\Core\Database;
use App\Core\TenantContext;
use App\Services\LogService;

abstract class Model
{
    protected Database $db;
    protected TenantContext $tenant;
    
    protected string $table;
    protected string $primaryKey = 'id';
    protected bool $tenantScoped = true;
    protected bool $timestamps = true;
    
    protected array $fillable = [];
    protected array $guarded = ['id'];
    protected array $casts = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tenant = TenantContext::getInstance();
    }

    public function find(int $id): ?array
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

    public function findAll(array $conditions = [], int $limit = 100, int $offset = 0): array
    {
        $where = ['1=1'];
        $params = [];

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

    public function create(array $data): int
    {
        $data = $this->filterFillable($data);

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $data['empresa_id'] = $this->tenant->requireTenant();
        }

        if ($this->timestamps) {
            $data['created_at'] = now();
            $data['updated_at'] = now();
        }

        $id = $this->db->insert($this->table, $data);

        LogService::audit('Model created', [
            'table' => $this->table,
            'id' => $id,
            'tenant_id' => $data['empresa_id'] ?? null
        ]);

        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->filterFillable($data);

        if ($this->timestamps) {
            $data['updated_at'] = now();
        }

        $where = "{$this->primaryKey} = :id";
        $params = ['id' => $id];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $where .= " AND empresa_id = :tenant_id";
            $params['tenant_id'] = $this->tenant->requireTenant();
        }

        $affected = $this->db->update($this->table, $data, $where, $params);

        if ($affected > 0) {
            LogService::audit('Model updated', [
                'table' => $this->table,
                'id' => $id,
                'tenant_id' => $params['tenant_id'] ?? null
            ]);
        }

        return $affected > 0;
    }

    public function delete(int $id): bool
    {
        $where = "{$this->primaryKey} = :id";
        $params = ['id' => $id];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $where .= " AND empresa_id = :tenant_id";
            $params['tenant_id'] = $this->tenant->requireTenant();
        }

        $affected = $this->db->delete($this->table, $where, $params);

        if ($affected > 0) {
            LogService::audit('Model deleted', [
                'table' => $this->table,
                'id' => $id,
                'tenant_id' => $params['tenant_id'] ?? null
            ]);
        }

        return $affected > 0;
    }

    public function count(array $conditions = []): int
    {
        $where = ['1=1'];
        $params = [];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $where[] = "empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        foreach ($conditions as $key => $value) {
            $where[] = "{$key} = ?";
            $params[] = $value;
        }

        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE " . implode(' AND ', $where);

        $result = $this->db->fetch($sql, $params);

        return (int) ($result['total'] ?? 0);
    }

    public function exists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return array_diff_key($data, array_flip($this->guarded));
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function cast(array $data): array
    {
        foreach ($this->casts as $key => $type) {
            if (!isset($data[$key])) {
                continue;
            }

            $data[$key] = match($type) {
                'int', 'integer' => (int) $data[$key],
                'float', 'double' => (float) $data[$key],
                'bool', 'boolean' => (bool) $data[$key],
                'string' => (string) $data[$key],
                'array', 'json' => json_decode($data[$key], true),
                'datetime' => $data[$key],
                default => $data[$key]
            };
        }

        return $data;
    }

    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->db->commit();
    }

    public function rollback(): bool
    {
        return $this->db->rollback();
    }
}
