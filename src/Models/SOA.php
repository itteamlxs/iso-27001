<?php

namespace App\Models;

use App\Models\Base\Model;
use App\Services\LogService;

class SOA extends Model
{
    protected string $table = 'soa_entries';
    protected bool $tenantScoped = true;
    
    protected array $fillable = [
        'control_id',
        'aplicable',
        'estado',
        'justificacion_no_aplicable',
        'fecha_evaluacion',
        'evaluado_por',
        'notas'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'empresa_id' => 'int',
        'control_id' => 'int',
        'aplicable' => 'bool',
        'evaluado_por' => 'int'
    ];

    public function findByControl(int $controlId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE control_id = ?";
        $params = [$controlId];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " LIMIT 1";

        $result = $this->db->fetch($sql, $params);

        return $result ? $this->cast($result) : null;
    }

    public function findWithControl(int $id): ?array
    {
        $sql = "SELECT s.*, c.codigo, c.nombre, c.descripcion, c.objetivo,
                       d.codigo as dominio_codigo, d.nombre as dominio_nombre
                FROM {$this->table} s
                INNER JOIN controles c ON s.control_id = c.id
                INNER JOIN controles_dominio d ON c.dominio_id = d.id
                WHERE s.id = ?";
        $params = [$id];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND s.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " LIMIT 1";

        $result = $this->db->fetch($sql, $params);

        return $result ? $this->cast($result) : null;
    }

    public function getAllWithControles(array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $where[] = "s.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        if (!empty($filters['dominio_id'])) {
            $where[] = "c.dominio_id = ?";
            $params[] = $filters['dominio_id'];
        }

        if (isset($filters['aplicable'])) {
            $where[] = "s.aplicable = ?";
            $params[] = $filters['aplicable'];
        }

        if (!empty($filters['estado'])) {
            $where[] = "s.estado = ?";
            $params[] = $filters['estado'];
        }

        $sql = "SELECT s.*, c.codigo, c.nombre, c.descripcion,
                       d.codigo as dominio_codigo, d.nombre as dominio_nombre,
                       u.nombre as evaluado_por_nombre
                FROM {$this->table} s
                INNER JOIN controles c ON s.control_id = c.id
                INNER JOIN controles_dominio d ON c.dominio_id = d.id
                LEFT JOIN usuarios u ON s.evaluado_por = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.codigo ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function evaluate(int $id, array $data, int $evaluadorId): bool
    {
        $data['fecha_evaluacion'] = now();
        $data['evaluado_por'] = $evaluadorId;

        if (isset($data['aplicable']) && !$data['aplicable']) {
            if (empty($data['justificacion_no_aplicable'])) {
                throw new \InvalidArgumentException('Se requiere justificación para controles no aplicables');
            }
            $data['estado'] = 'no_implementado';
        }

        if (isset($data['aplicable']) && $data['aplicable']) {
            $data['justificacion_no_aplicable'] = null;
        }

        $result = $this->update($id, $data);

        if ($result) {
            LogService::audit('SOA evaluated', [
                'soa_id' => $id,
                'control_id' => $this->find($id)['control_id'],
                'aplicable' => $data['aplicable'],
                'estado' => $data['estado'] ?? null,
                'evaluado_por' => $evaluadorId
            ]);
        }

        return $result;
    }

    public function getEstadisticas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN aplicable = 1 THEN 1 ELSE 0 END) as aplicables,
                    SUM(CASE WHEN aplicable = 0 THEN 1 ELSE 0 END) as no_aplicables,
                    SUM(CASE WHEN estado = 'implementado' THEN 1 ELSE 0 END) as implementados,
                    SUM(CASE WHEN estado = 'parcial' THEN 1 ELSE 0 END) as parciales,
                    SUM(CASE WHEN estado = 'no_implementado' THEN 1 ELSE 0 END) as no_implementados
                FROM {$this->table}";
        $params = [];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " WHERE empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        return $this->db->fetch($sql, $params) ?? [];
    }

    public function getEstadisticasPorDominio(): array
    {
        $sql = "SELECT 
                    d.codigo,
                    d.nombre,
                    COUNT(s.id) as total,
                    SUM(CASE WHEN s.aplicable = 1 THEN 1 ELSE 0 END) as aplicables,
                    SUM(CASE WHEN s.estado = 'implementado' THEN 1 ELSE 0 END) as implementados,
                    SUM(CASE WHEN s.estado = 'parcial' THEN 1 ELSE 0 END) as parciales
                FROM controles_dominio d
                INNER JOIN controles c ON d.id = c.dominio_id
                INNER JOIN {$this->table} s ON c.id = s.control_id";
        $params = [];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " WHERE s.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " GROUP BY d.id, d.codigo, d.nombre ORDER BY d.codigo ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getCumplimiento(): float
    {
        $stats = $this->getEstadisticas();

        if (!$stats || $stats['aplicables'] == 0) {
            return 0.0;
        }

        return round(($stats['implementados'] / $stats['aplicables']) * 100, 2);
    }

    public function getControlesNoImplementados(): array
    {
        $sql = "SELECT s.*, c.codigo, c.nombre, d.nombre as dominio_nombre
                FROM {$this->table} s
                INNER JOIN controles c ON s.control_id = c.id
                INNER JOIN controles_dominio d ON c.dominio_id = d.id
                WHERE s.aplicable = 1 
                  AND s.estado IN ('no_implementado', 'parcial')";
        $params = [];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND s.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " ORDER BY c.codigo ASC";

        return $this->db->fetchAll($sql, $params);
    }
}
