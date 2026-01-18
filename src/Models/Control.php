<?php

namespace App\Models;

use App\Models\Base\Model;

class Control extends Model
{
    protected string $table = 'controles';
    protected bool $tenantScoped = false;
    
    protected array $fillable = [
        'dominio_id',
        'codigo',
        'nombre',
        'descripcion',
        'objetivo'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'dominio_id' => 'int'
    ];

    public function findByCodigo(string $codigo): ?array
    {
        $result = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE codigo = ? LIMIT 1",
            [$codigo]
        );

        return $result ? $this->cast($result) : null;
    }

    public function findByDominio(int $dominioId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE dominio_id = ? ORDER BY codigo ASC",
            [$dominioId]
        );
    }

    public function findWithDominio(int $id): ?array
    {
        $result = $this->db->fetch(
            "SELECT c.*, d.codigo as dominio_codigo, d.nombre as dominio_nombre
             FROM {$this->table} c
             INNER JOIN controles_dominio d ON c.dominio_id = d.id
             WHERE c.id = ?
             LIMIT 1",
            [$id]
        );

        return $result ? $this->cast($result) : null;
    }

    public function getAllWithDominio(): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, d.codigo as dominio_codigo, d.nombre as dominio_nombre
             FROM {$this->table} c
             INNER JOIN controles_dominio d ON c.dominio_id = d.id
             ORDER BY c.codigo ASC"
        );
    }

    public function countByDominio(): array
    {
        return $this->db->fetchAll(
            "SELECT d.codigo, d.nombre, COUNT(c.id) as total
             FROM controles_dominio d
             LEFT JOIN controles c ON d.id = c.dominio_id
             GROUP BY d.id, d.codigo, d.nombre
             ORDER BY d.codigo ASC"
        );
    }

    public function search(string $query): array
    {
        $searchTerm = "%{$query}%";
        
        return $this->db->fetchAll(
            "SELECT c.*, d.nombre as dominio_nombre
             FROM {$this->table} c
             INNER JOIN controles_dominio d ON c.dominio_id = d.id
             WHERE c.codigo LIKE ? 
                OR c.nombre LIKE ? 
                OR c.descripcion LIKE ?
             ORDER BY c.codigo ASC
             LIMIT 50",
            [$searchTerm, $searchTerm, $searchTerm]
        );
    }

    public function getDominios(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM controles_dominio ORDER BY codigo ASC"
        );
    }

    public function getDominio(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM controles_dominio WHERE id = ? LIMIT 1",
            [$id]
        );
    }
}
