<?php

namespace App\Models;

use App\Models\Base\Model;
use App\Models\Base\SoftDelete;

class Accion extends Model
{
    use SoftDelete;

    protected string $table = 'acciones';
    protected bool $tenantScoped = false;
    protected string $deletedAtColumn = 'estado_accion';
    protected string $deletedValue = 'eliminada';
    protected string $activeValue = 'activo';
    
    protected array $fillable = [
        'gap_id',
        'descripcion',
        'responsable',
        'fecha_compromiso',
        'fecha_completado',
        'estado',
        'notas'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'gap_id' => 'int'
    ];

    public function findByGap(int $gapId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} 
             WHERE gap_id = ? AND estado_accion = ?
             ORDER BY fecha_compromiso ASC",
            [$gapId, $this->activeValue]
        );
    }

    public function completar(int $id): bool
    {
        $result = $this->update($id, [
            'estado' => 'completada',
            'fecha_completado' => now()
        ]);

        if ($result) {
            $accion = $this->find($id);
            if ($accion) {
                $gapModel = new Gap();
                $gapModel->verificarCierre($accion['gap_id']);
            }
        }

        return $result;
    }

    public function reabrir(int $id): bool
    {
        return $this->update($id, [
            'estado' => 'en_progreso',
            'fecha_completado' => null
        ]);
    }

    public function getVencidas(): array
    {
        $sql = "SELECT a.*, g.brecha, c.codigo as control_codigo
                FROM {$this->table} a
                INNER JOIN gap_items g ON a.gap_id = g.id
                INNER JOIN soa_entries s ON g.soa_id = s.id
                INNER JOIN controles c ON s.control_id = c.id
                WHERE a.estado != 'completada' 
                  AND a.estado_accion = ?
                  AND a.fecha_compromiso < CURDATE()
                  AND g.estado_gap = 'activo'";
        $params = [$this->activeValue];

        if ($this->tenant->hasTenant()) {
            $sql .= " AND s.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " ORDER BY a.fecha_compromiso ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getPendientes(): array
    {
        return $this->findAll(['estado' => 'pendiente'], 100);
    }

    public function getEnProgreso(): array
    {
        return $this->findAll(['estado' => 'en_progreso'], 100);
    }

    public function getEstadisticas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'en_progreso' THEN 1 ELSE 0 END) as en_progreso,
                    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                    SUM(CASE WHEN fecha_compromiso < CURDATE() AND estado != 'completada' THEN 1 ELSE 0 END) as vencidas
                FROM {$this->table} a
                INNER JOIN gap_items g ON a.gap_id = g.id
                INNER JOIN soa_entries s ON g.soa_id = s.id
                WHERE a.estado_accion = ? AND g.estado_gap = 'activo'";
        $params = [$this->activeValue];

        if ($this->tenant->hasTenant()) {
            $sql .= " AND s.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        return $this->db->fetch($sql, $params) ?? [];
    }
}
