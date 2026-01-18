<?php

namespace App\Repositories;

use App\Repositories\Base\Repository;
use App\Models\Requerimiento;
use App\Services\LogService;

class RequerimientoRepository extends Repository
{
    protected string $model = Requerimiento::class;

    public function getAllWithDetalle(): array
    {
        $requerimientos = $this->modelInstance->getAllWithBase();

        return array_map(function($req) {
            $progreso = $this->modelInstance->calcularProgreso($req['requerimiento_id']);
            return array_merge($req, $progreso);
        }, $requerimientos);
    }

    public function getWithDetalle(int $id): ?array
    {
        $requerimiento = $this->modelInstance->findWithBase($id);

        if (!$requerimiento) {
            return null;
        }

        $controles = $this->modelInstance->getControles($requerimiento['requerimiento_id']);
        $evidencias = $this->modelInstance->getEvidencias($requerimiento['requerimiento_id']);
        $progreso = $this->modelInstance->calcularProgreso($requerimiento['requerimiento_id']);

        return array_merge($requerimiento, [
            'controles' => $controles,
            'evidencias' => $evidencias,
            'progreso' => $progreso
        ]);
    }

    public function verificarCompletitud(int $requerimientoId): bool
    {
        $result = $this->modelInstance->verificarCompletitud($requerimientoId);

        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    public function verificarTodosLosRequerimientos(): array
    {
        $resultados = $this->modelInstance->verificarTodos();

        if (!empty(array_filter($resultados))) {
            $this->clearCache();
        }

        LogService::info('All requirements verified', [
            'completados' => count(array_filter($resultados))
        ]);

        return $resultados;
    }

    public function actualizarEstado(int $id, string $estado, ?string $observaciones = null): bool
    {
        return $this->transaction(function() use ($id, $estado, $observaciones) {
            
            $data = ['estado' => $estado];

            if ($estado === 'completado') {
                $data['fecha_completado'] = now();
            }

            if ($observaciones !== null) {
                $data['observaciones'] = $observaciones;
            }

            $result = $this->update($id, $data);

            if ($result) {
                LogService::audit('Requirement status updated', [
                    'requerimiento_id' => $id,
                    'estado' => $estado
                ]);

                $this->clearCache($id);
            }

            return $result;
        });
    }

    public function getEstadisticas(): array
    {
        $cacheKey = $this->getCacheKey('stats', 'general');

        return $this->cache->remember($cacheKey, function() {
            $stats = $this->modelInstance->getEstadisticas();
            
            $requerimientos = $this->getAllWithDetalle();
            $progresoTotal = 0;
            
            foreach ($requerimientos as $req) {
                $progresoTotal += $req['progreso_general'] ?? 0;
            }

            $progresoPromedio = count($requerimientos) > 0 
                ? round($progresoTotal / count($requerimientos), 2) 
                : 0;

            return array_merge($stats, [
                'progreso_promedio' => $progresoPromedio
            ]);
        }, 300);
    }

    public function getProgreso(int $requerimientoId): array
    {
        $requerimiento = $this->modelInstance->findWithBase($requerimientoId);

        if (!$requerimiento) {
            return [];
        }

        return $this->modelInstance->calcularProgreso($requerimiento['requerimiento_id']);
    }

    public function getPendientes(): array
    {
        $requerimientos = $this->modelInstance->getAllWithBase();

        return array_filter($requerimientos, function($req) {
            return $req['estado'] === 'pendiente';
        });
    }

    public function getCompletados(): array
    {
        $requerimientos = $this->modelInstance->getAllWithBase();

        return array_filter($requerimientos, function($req) {
            return $req['estado'] === 'completado';
        });
    }

    protected function clearCache(?int $id = null): void
    {
        parent::clearCache($id);
        $this->cache->delete($this->getCacheKey('stats', 'general'));
    }
}
