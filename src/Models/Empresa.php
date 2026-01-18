<?php

namespace App\Models;

use App\Models\Base\Model;

class Empresa extends Model
{
    protected string $table = 'empresas';
    protected bool $tenantScoped = false;
    
    protected array $fillable = [
        'nombre',
        'ruc',
        'sector',
        'telefono',
        'email',
        'direccion',
        'metadata'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'metadata' => 'json'
    ];

    public function findByRuc(string $ruc): ?array
    {
        $result = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE ruc = ? LIMIT 1",
            [$ruc]
        );

        return $result ? $this->cast($result) : null;
    }

    public function getUsuarios(int $empresaId): array
    {
        return $this->db->fetchAll(
            "SELECT id, nombre, email, rol, estado, ultimo_acceso, created_at
             FROM usuarios
             WHERE empresa_id = ?
             ORDER BY created_at DESC",
            [$empresaId]
        );
    }

    public function getEstadisticas(int $empresaId): array
    {
        $stats = [];

        $usuarios = $this->db->fetch(
            "SELECT COUNT(*) as total, 
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos
             FROM usuarios 
             WHERE empresa_id = ?",
            [$empresaId]
        );
        $stats['usuarios'] = $usuarios;

        $controles = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'implementado' THEN 1 ELSE 0 END) as implementados,
                SUM(CASE WHEN estado = 'parcial' THEN 1 ELSE 0 END) as parciales,
                SUM(CASE WHEN aplicable = 1 THEN 1 ELSE 0 END) as aplicables
             FROM soa_entries 
             WHERE empresa_id = ?",
            [$empresaId]
        );
        $stats['controles'] = $controles;

        $gaps = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado_gap = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado_gap = 'cerrado' THEN 1 ELSE 0 END) as cerrados
             FROM gap_items g
             INNER JOIN soa_entries s ON g.soa_id = s.id
             WHERE s.empresa_id = ?",
            [$empresaId]
        );
        $stats['gaps'] = $gaps;

        $evidencias = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado_validacion = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
                SUM(CASE WHEN estado_validacion = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado_validacion = 'rechazada' THEN 1 ELSE 0 END) as rechazadas
             FROM evidencias 
             WHERE empresa_id = ?",
            [$empresaId]
        );
        $stats['evidencias'] = $evidencias;

        $requerimientos = $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso
             FROM empresa_requerimientos 
             WHERE empresa_id = ?",
            [$empresaId]
        );
        $stats['requerimientos'] = $requerimientos;

        return $stats;
    }

    public function getCumplimiento(int $empresaId): float
    {
        $result = $this->db->fetch(
            "SELECT 
                COUNT(*) as aplicables,
                SUM(CASE WHEN estado = 'implementado' THEN 1 ELSE 0 END) as implementados
             FROM soa_entries 
             WHERE empresa_id = ? AND aplicable = 1",
            [$empresaId]
        );

        if (!$result || $result['aplicables'] == 0) {
            return 0.0;
        }

        return round(($result['implementados'] / $result['aplicables']) * 100, 2);
    }
}
