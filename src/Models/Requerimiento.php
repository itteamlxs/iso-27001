<?php

namespace App\Models;

use App\Models\Base\Model;
use App\Services\LogService;

class Requerimiento extends Model
{
    protected string $table = 'empresa_requerimientos';
    protected bool $tenantScoped = true;
    
    protected array $fillable = [
        'requerimiento_id',
        'estado',
        'fecha_completado',
        'observaciones'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'empresa_id' => 'int',
        'requerimiento_id' => 'int'
    ];

    public function findWithBase(int $id): ?array
    {
        $sql = "SELECT er.*, rb.numero, rb.identificador, rb.descripcion
                FROM {$this->table} er
                INNER JOIN requerimientos_base rb ON er.requerimiento_id = rb.id
                WHERE er.id = ?";
        $params = [$id];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND er.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " LIMIT 1";

        $result = $this->db->fetch($sql, $params);

        return $result ? $this->cast($result) : null;
    }

    public function getAllWithBase(): array
    {
        $sql = "SELECT er.*, rb.numero, rb.identificador, rb.descripcion
                FROM {$this->table} er
                INNER JOIN requerimientos_base rb ON er.requerimiento_id = rb.id";
        $params = [];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " WHERE er.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " ORDER BY rb.numero ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getControles(int $requerimientoId): array
    {
        $sql = "SELECT c.id, c.codigo, c.nombre, s.estado, s.aplicable
                FROM requerimientos_controles rc
                INNER JOIN controles c ON rc.control_id = c.id
                INNER JOIN soa_entries s ON c.id = s.control_id
                WHERE rc.requerimiento_base_id = ?";
        $params = [$requerimientoId];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND s.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " ORDER BY c.codigo ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getEvidencias(int $requerimientoId): array
    {
        $sql = "SELECT e.*, c.codigo as control_codigo, c.nombre as control_nombre
                FROM evidencias e
                INNER JOIN requerimientos_controles rc ON e.control_id = rc.control_id
                INNER JOIN controles c ON e.control_id = c.id
                WHERE rc.requerimiento_base_id = ?";
        $params = [$requerimientoId];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND e.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " ORDER BY e.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function verificarCompletitud(int $empresaRequerimientoId): bool
    {
        $requerimiento = $this->find($empresaRequerimientoId);

        if (!$requerimiento) {
            return false;
        }

        $controles = $this->getControles($requerimiento['requerimiento_id']);

        foreach ($controles as $control) {
            if (!$control['aplicable']) {
                continue;
            }

            if ($control['estado'] !== 'implementado') {
                return false;
            }

            $evidencias = $this->db->fetchAll(
                "SELECT estado_validacion FROM evidencias 
                 WHERE control_id = ? AND empresa_id = ?",
                [$control['id'], $this->tenant->requireTenant()]
            );

            if (empty($evidencias)) {
                return false;
            }

            $tieneAprobada = false;
            foreach ($evidencias as $evidencia) {
                if ($evidencia['estado_validacion'] === 'aprobada') {
                    $tieneAprobada = true;
                    break;
                }
            }

            if (!$tieneAprobada) {
                return false;
            }
        }

        $this->update($empresaRequerimientoId, [
            'estado' => 'completado',
            'fecha_completado' => now(),
            'observaciones' => 'Completado automáticamente - Todos los controles implementados y evidencias aprobadas'
        ]);

        LogService::audit('Requirement completed', [
            'empresa_requerimiento_id' => $empresaRequerimientoId,
            'requerimiento_id' => $requerimiento['requerimiento_id']
        ]);

        return true;
    }

    public function calcularProgreso(int $requerimientoId): array
    {
        $controles = $this->getControles($requerimientoId);
        
        $total = 0;
        $implementados = 0;
        $conEvidencia = 0;

        foreach ($controles as $control) {
            if (!$control['aplicable']) {
                continue;
            }

            $total++;

            if ($control['estado'] === 'implementado') {
                $implementados++;
            }

            $evidenciasAprobadas = $this->db->fetch(
                "SELECT COUNT(*) as total FROM evidencias 
                 WHERE control_id = ? AND empresa_id = ? AND estado_validacion = 'aprobada'",
                [$control['id'], $this->tenant->requireTenant()]
            );

            if ($evidenciasAprobadas['total'] > 0) {
                $conEvidencia++;
            }
        }

        $progresoControles = $total > 0 ? round(($implementados / $total) * 100, 2) : 0;
        $progresoEvidencias = $total > 0 ? round(($conEvidencia / $total) * 100, 2) : 0;
        $progresoGeneral = round(($progresoControles + $progresoEvidencias) / 2, 2);

        return [
            'total_controles' => $total,
            'controles_implementados' => $implementados,
            'controles_con_evidencia' => $conEvidencia,
            'progreso_controles' => $progresoControles,
            'progreso_evidencias' => $progresoEvidencias,
            'progreso_general' => $progresoGeneral
        ];
    }

    public function getEstadisticas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                    SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados
                FROM {$this->table}";
        $params = [];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " WHERE empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        return $this->db->fetch($sql, $params) ?? [];
    }

    public function verificarTodos(): array
    {
        $requerimientos = $this->findAll();
        $resultados = [];

        foreach ($requerimientos as $req) {
            $completado = $this->verificarCompletitud($req['id']);
            $resultados[$req['id']] = $completado;
        }

        return $resultados;
    }
}
