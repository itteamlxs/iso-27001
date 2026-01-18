<?php

namespace App\Models;

use App\Models\Base\Model;
use App\Models\Base\SoftDelete;

class Gap extends Model
{
    use SoftDelete;

    protected string $table = 'gap_items';
    protected bool $tenantScoped = true;
    protected string $deletedAtColumn = 'estado_gap';
    protected string $deletedValue = 'eliminado';
    protected string $activeValue = 'activo';
    
    protected array $fillable = [
        'soa_id',
        'brecha',
        'objetivo',
        'prioridad',
        'responsable',
        'fecha_compromiso',
        'fecha_real_cierre'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'soa_id' => 'int'
    ];

    public function findWithControl(int $id): ?array
    {
        $sql = "SELECT g.*, 
                       s.control_id,
                       c.codigo as control_codigo,
                       c.nombre as control_nombre,
                       d.nombre as dominio_nombre
                FROM {$this->table} g
                INNER JOIN soa_entries s ON g.soa_id = s.id
                INNER JOIN controles c ON s.control_id = c.id
                INNER JOIN controles_dominio d ON c.dominio_id = d.id
                WHERE g.id = ? AND g.estado_gap = ?";
        $params = [$id, $this->activeValue];

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
        $where = ["g.estado_gap = ?"];
        $params = [$this->activeValue];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $where[] = "s.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        if (!empty($filters['prioridad'])) {
            $where[] = "g.prioridad = ?";
            $params[] = $filters['prioridad'];
        }

        if (!empty($filters['dominio_id'])) {
            $where[] = "c.dominio_id = ?";
            $params[] = $filters['dominio_id'];
        }

        $sql = "SELECT g.*, 
                       c.codigo as control_codigo,
                       c.nombre as control_nombre,
                       d.nombre as dominio_nombre
                FROM {$this->table} g
                INNER JOIN soa_entries s ON g.soa_id = s.id
                INNER JOIN controles c ON s.control_id = c.id
                INNER JOIN controles_dominio d ON c.dominio_id = d.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY 
                    CASE g.prioridad 
                        WHEN 'alta' THEN 1 
                        WHEN 'media' THEN 2 
                        WHEN 'baja' THEN 3 
                    END,
                    g.fecha_compromiso ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function calcularAvance(int $gapId): float
    {
        $result = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas
             FROM acciones 
             WHERE gap_id = ? AND estado_accion = 'activo'",
            [$gapId]
        );

        if (!$result || $result['total'] == 0) {
            return 0.0;
        }

        return round(($result['completadas'] / $result['total']) * 100, 2);
    }

    public function verificarCierre(int $gapId): bool
    {
        $avance = $this->calcularAvance($gapId);

        if ($avance >= 100) {
            $this->update($gapId, [
                'fecha_real_cierre' => now()
            ]);

            $this->softDelete($gapId);

            return true;
        }

        return false;
    }

    public function getAcciones(int $gapId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM acciones 
             WHERE gap_id = ? AND estado_accion = 'activo'
             ORDER BY fecha_compromiso ASC",
            [$gapId]
        );
    }

    public function getEstadisticas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN prioridad = 'alta' THEN 1 ELSE 0 END) as alta,
                    SUM(CASE WHEN prioridad = 'media' THEN 1 ELSE 0 END) as media,
                    SUM(CASE WHEN prioridad = 'baja' THEN 1 ELSE 0 END) as baja,
                    SUM(CASE WHEN estado_gap = 'cerrado' THEN 1 ELSE 0 END) as cerrados
                FROM {$this->table} g
                INNER JOIN soa_entries s ON g.soa_id = s.id
                WHERE g.estado_gap IN ('activo', 'cerrado')";
        $params = [];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND s.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        return $this->db->fetch($sql, $params) ?? [];
    }

    public function getGapsCriticos(): array
    {
        $sql = "SELECT g.*, 
                       c.codigo as control_codigo,
                       c.nombre as control_nombre
                FROM {$this->table} g
                INNER JOIN soa_entries s ON g.soa_id = s.id
                INNER JOIN controles c ON s.control_id = c.id
                WHERE g.estado_gap = 'activo' 
                  AND g.prioridad = 'alta'";
        $params = [];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND s.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " ORDER BY g.fecha_compromiso ASC LIMIT 10";

        return $this->db->fetchAll($sql, $params);
    }

    protected function cascadeSoftDelete(int $gapId): void
    {
        $this->db->update('acciones', [
            'estado_accion' => 'eliminada'
        ], 'gap_id = :gap_id', ['gap_id' => $gapId]);
    }
}
