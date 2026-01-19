<?php

namespace App\Services;

use App\Core\Database;
use App\Core\TenantContext;

class MetricsService
{
    private Database $db;
    private TenantContext $tenant;
    private CacheService $cache;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tenant = TenantContext::getInstance();
        $this->cache = new CacheService();
    }

    public function getDashboardMetrics(): array
    {
        $cacheKey = $this->getCacheKey('dashboard_metrics');

        return $this->cache->remember($cacheKey, function() {
            return [
                'cumplimiento' => $this->getCumplimientoGeneral(),
                'controles' => $this->getEstadisticasControles(),
                'gaps' => $this->getEstadisticasGaps(),
                'evidencias' => $this->getEstadisticasEvidencias(),
                'requerimientos' => $this->getEstadisticasRequerimientos(),
                'acciones_vencidas' => $this->getAccionesVencidas(),
                'gaps_criticos' => $this->getGapsCriticos(),
                'avance_dominios' => $this->getAvancePorDominio()
            ];
        }, 300);
    }

    private function getCumplimientoGeneral(): array
    {
        $sql = "SELECT 
                    COUNT(*) as aplicables,
                    SUM(CASE WHEN estado = 'implementado' THEN 1 ELSE 0 END) as implementados
                FROM soa_entries
                WHERE aplicable = 1";
        $params = [];

        if ($this->tenant->hasTenant()) {
            $sql .= " AND empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $result = $this->db->fetch($sql, $params);

        $porcentaje = $result['aplicables'] > 0 
            ? round(($result['implementados'] / $result['aplicables']) * 100, 2) 
            : 0;

        return [
            'aplicables' => (int) $result['aplicables'],
            'implementados' => (int) $result['implementados'],
            'porcentaje' => $porcentaje
        ];
    }

    private function getEstadisticasControles(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN aplicable = 1 THEN 1 ELSE 0 END) as aplicables,
                    SUM(CASE WHEN estado = 'implementado' THEN 1 ELSE 0 END) as implementados,
                    SUM(CASE WHEN estado = 'parcial' THEN 1 ELSE 0 END) as parciales,
                    SUM(CASE WHEN estado = 'no_implementado' THEN 1 ELSE 0 END) as no_implementados
                FROM soa_entries";
        $params = [];

        if ($this->tenant->hasTenant()) {
            $sql .= " WHERE empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        return $this->db->fetch($sql, $params);
    }

    private function getEstadisticasGaps(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN prioridad = 'alta' THEN 1 ELSE 0 END) as alta,
                    SUM(CASE WHEN prioridad = 'media' THEN 1 ELSE 0 END) as media,
                    SUM(CASE WHEN prioridad = 'baja' THEN 1 ELSE 0 END) as baja
                FROM gap_items g
                INNER JOIN soa_entries s ON g.soa_id = s.id
                WHERE g.estado_gap = 'activo'";
        $params = [];

        if ($this->tenant->hasTenant()) {
            $sql .= " AND s.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        return $this->db->fetch($sql, $params);
    }

    private function getEstadisticasEvidencias(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado_validacion = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado_validacion = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
                    SUM(CASE WHEN estado_validacion = 'rechazada' THEN 1 ELSE 0 END) as rechazadas
                FROM evidencias";
        $params = [];

        if ($this->tenant->hasTenant()) {
            $sql .= " WHERE empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        return $this->db->fetch($sql, $params);
    }

    private function getEstadisticasRequerimientos(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
                    SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso
                FROM empresa_requerimientos";
        $params = [];

        if ($this->tenant->hasTenant()) {
            $sql .= " WHERE empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        return $this->db->fetch($sql, $params);
    }

    private function getAccionesVencidas(): int
    {
        $sql = "SELECT COUNT(*) as total
                FROM acciones a
                INNER JOIN gap_items g ON a.gap_id = g.id
                INNER JOIN soa_entries s ON g.soa_id = s.id
                WHERE a.estado != 'completada' 
                  AND a.estado_accion = 'activo'
                  AND a.fecha_compromiso < CURDATE()
                  AND g.estado_gap = 'activo'";
        $params = [];

        if ($this->tenant->hasTenant()) {
            $sql .= " AND s.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $result = $this->db->fetch($sql, $params);

        return (int) $result['total'];
    }

    private function getGapsCriticos(): int
    {
        $sql = "SELECT COUNT(*) as total
                FROM gap_items g
                INNER JOIN soa_entries s ON g.soa_id = s.id
                WHERE g.estado_gap = 'activo' 
                  AND g.prioridad = 'alta'";
        $params = [];

        if ($this->tenant->hasTenant()) {
            $sql .= " AND s.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $result = $this->db->fetch($sql, $params);

        return (int) $result['total'];
    }

    private function getAvancePorDominio(): array
    {
        $sql = "SELECT 
                    d.codigo,
                    d.nombre,
                    COUNT(s.id) as total,
                    SUM(CASE WHEN s.aplicable = 1 THEN 1 ELSE 0 END) as aplicables,
                    SUM(CASE WHEN s.estado = 'implementado' THEN 1 ELSE 0 END) as implementados
                FROM controles_dominio d
                INNER JOIN controles c ON d.id = c.dominio_id
                INNER JOIN soa_entries s ON c.id = s.control_id";
        $params = [];

        if ($this->tenant->hasTenant()) {
            $sql .= " WHERE s.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " GROUP BY d.id, d.codigo, d.nombre ORDER BY d.codigo ASC";

        $resultados = $this->db->fetchAll($sql, $params);

        return array_map(function($dominio) {
            $porcentaje = $dominio['aplicables'] > 0 
                ? round(($dominio['implementados'] / $dominio['aplicables']) * 100, 2) 
                : 0;

            return [
                'codigo' => $dominio['codigo'],
                'nombre' => $dominio['nombre'],
                'total' => (int) $dominio['total'],
                'aplicables' => (int) $dominio['aplicables'],
                'implementados' => (int) $dominio['implementados'],
                'porcentaje' => $porcentaje
            ];
        }, $resultados);
    }

    public function getTimeline(int $dias = 30): array
    {
        $sql = "SELECT 
                    DATE(created_at) as fecha,
                    COUNT(*) as total
                FROM soa_entries
                WHERE estado = 'implementado' 
                  AND fecha_evaluacion >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        $params = [$dias];

        if ($this->tenant->hasTenant()) {
            $sql .= " AND empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " GROUP BY DATE(created_at) ORDER BY fecha ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function clearCache(): void
    {
        $cacheKey = $this->getCacheKey('dashboard_metrics');
        $this->cache->delete($cacheKey);
    }

    private function getCacheKey(string $key): string
    {
        $tenantId = $this->tenant->hasTenant() ? $this->tenant->getTenant() : 'global';
        return "metrics:{$tenantId}:{$key}";
    }
}
