<?php

namespace App\Models;

use App\Models\Base\Model;

class Evidencia extends Model
{
    protected string $table = 'evidencias';
    protected bool $tenantScoped = true;
    
    protected array $fillable = [
        'control_id',
        'tipo',
        'descripcion',
        'archivo',
        'nombre_original',
        'ruta',
        'tamanio',
        'mime_type',
        'hash',
        'estado_validacion',
        'validado_por',
        'fecha_validacion',
        'comentarios_validacion'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'empresa_id' => 'int',
        'control_id' => 'int',
        'tamanio' => 'int',
        'validado_por' => 'int'
    ];

    public function findWithControl(int $id): ?array
    {
        $sql = "SELECT e.*, 
                       c.codigo as control_codigo,
                       c.nombre as control_nombre,
                       d.nombre as dominio_nombre,
                       u.nombre as validado_por_nombre
                FROM {$this->table} e
                INNER JOIN controles c ON e.control_id = c.id
                INNER JOIN controles_dominio d ON c.dominio_id = d.id
                LEFT JOIN usuarios u ON e.validado_por = u.id
                WHERE e.id = ?";
        $params = [$id];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND e.empresa_id = ?";
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
            $where[] = "e.empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        if (!empty($filters['control_id'])) {
            $where[] = "e.control_id = ?";
            $params[] = $filters['control_id'];
        }

        if (!empty($filters['tipo'])) {
            $where[] = "e.tipo = ?";
            $params[] = $filters['tipo'];
        }

        if (!empty($filters['estado_validacion'])) {
            $where[] = "e.estado_validacion = ?";
            $params[] = $filters['estado_validacion'];
        }

        $sql = "SELECT e.*, 
                       c.codigo as control_codigo,
                       c.nombre as control_nombre,
                       d.nombre as dominio_nombre,
                       u.nombre as validado_por_nombre
                FROM {$this->table} e
                INNER JOIN controles c ON e.control_id = c.id
                INNER JOIN controles_dominio d ON c.dominio_id = d.id
                LEFT JOIN usuarios u ON e.validado_por = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function findByControl(int $controlId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE control_id = ?";
        $params = [$controlId];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " ORDER BY created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function validar(int $id, bool $aprobada, int $validadorId, ?string $comentarios = null): bool
    {
        $estado = $aprobada ? 'aprobada' : 'rechazada';

        return $this->update($id, [
            'estado_validacion' => $estado,
            'validado_por' => $validadorId,
            'fecha_validacion' => now(),
            'comentarios_validacion' => $comentarios
        ]);
    }

    public function getPendientes(): array
    {
        return $this->findAll(['estado_validacion' => 'pendiente'], 100);
    }

    public function getAprobadas(): array
    {
        return $this->findAll(['estado_validacion' => 'aprobada'], 100);
    }

    public function getRechazadas(): array
    {
        return $this->findAll(['estado_validacion' => 'rechazada'], 100);
    }

    public function getEstadisticas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado_validacion = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado_validacion = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
                    SUM(CASE WHEN estado_validacion = 'rechazada' THEN 1 ELSE 0 END) as rechazadas,
                    SUM(tamanio) as tamanio_total
                FROM {$this->table}";
        $params = [];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " WHERE empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        return $this->db->fetch($sql, $params) ?? [];
    }

    public function getTipos(): array
    {
        return [
            'Política',
            'Procedimiento',
            'Registro',
            'Certificado',
            'Acta',
            'Informe',
            'Captura de pantalla',
            'Documento técnico',
            'Otro'
        ];
    }

    public function hashExists(string $hash): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE hash = ?";
        $params = [$hash];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        $sql .= " LIMIT 1";

        $result = $this->db->fetch($sql, $params);

        return $result !== null;
    }

    public function countByControl(int $controlId, ?string $estado = null): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE control_id = ?";
        $params = [$controlId];

        if ($this->tenantScoped && $this->tenant->hasTenant()) {
            $sql .= " AND empresa_id = ?";
            $params[] = $this->tenant->requireTenant();
        }

        if ($estado !== null) {
            $sql .= " AND estado_validacion = ?";
            $params[] = $estado;
        }

        $result = $this->db->fetch($sql, $params);

        return (int) ($result['total'] ?? 0);
    }
}
