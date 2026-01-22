<?php

namespace App\Repositories;

use App\Repositories\Base\Repository;
use App\Models\Requerimiento;

class RequerimientoRepository extends Repository
{
    protected string $model = Requerimiento::class;

    public function __construct()
    {
        parent::__construct();
    }

    public function getAllWithDetalle(): array
    {
        return $this->modelInstance->getAllWithBase();
    }

    public function getWithDetalle(int $id): ?array
    {
        return $this->modelInstance->findWithBase($id);
    }

    public function updateDetalle(int $id, array $data): bool
    {
        return $this->modelInstance->updateDetalle($id, $data);
    }

    public function getEstadisticas(): array
    {
        return $this->cache->remember('requerimientos_stats', function() {
            $requerimientos = $this->getAllWithDetalle();
            
            $stats = [
                'total' => count($requerimientos),
                'completados' => 0,
                'en_progreso' => 0,
                'pendientes' => 0
            ];

            foreach ($requerimientos as $req) {
                switch ($req['estado']) {
                    case 'completado':
                        $stats['completados']++;
                        break;
                    case 'en_progreso':
                        $stats['en_progreso']++;
                        break;
                    case 'pendiente':
                        $stats['pendientes']++;
                        break;
                }
            }

            return $stats;
        }, 300);
    }
}
