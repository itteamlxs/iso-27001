<?php

namespace App\Repositories;

use App\Repositories\Base\Repository;
use App\Models\Gap;
use App\Models\Accion;
use App\Models\SOA;
use App\Services\LogService;

class GapRepository extends Repository
{
    protected string $model = Gap::class;
    private Accion $accionModel;
    private SOA $soaModel;

    public function __construct()
    {
        parent::__construct();
        $this->accionModel = new Accion();
        $this->soaModel = new SOA();
    }

    public function createWithAcciones(array $gapData, array $acciones): int
    {
        return $this->transaction(function() use ($gapData, $acciones) {
            
            $soa = $this->soaModel->find($gapData['soa_id']);
            
            if (!$soa || !$soa['aplicable'] || $soa['estado'] === 'implementado') {
                throw new \InvalidArgumentException('Solo se pueden crear GAPs en controles aplicables no implementados');
            }

            $gapId = $this->create($gapData);

            foreach ($acciones as $accion) {
                $accion['gap_id'] = $gapId;
                $this->accionModel->create($accion);
            }

            LogService::audit('GAP created with actions', [
                'gap_id' => $gapId,
                'soa_id' => $gapData['soa_id'],
                'acciones_count' => count($acciones)
            ]);

            $this->clearCache();

            return $gapId;
        });
    }

    public function getWithAcciones(int $gapId): ?array
    {
        $gap = $this->modelInstance->findWithControl($gapId);

        if (!$gap) {
            return null;
        }

        $acciones = $this->modelInstance->getAcciones($gapId);
        $avance = $this->modelInstance->calcularAvance($gapId);

        return array_merge($gap, [
            'acciones' => $acciones,
            'avance' => $avance
        ]);
    }

    public function addAccion(int $gapId, array $accionData): int
    {
        return $this->transaction(function() use ($gapId, $accionData) {
            
            if (!$this->exists($gapId)) {
                throw new \InvalidArgumentException('GAP no encontrado');
            }

            $accionData['gap_id'] = $gapId;
            $accionId = $this->accionModel->create($accionData);

            LogService::audit('Action added to GAP', [
                'gap_id' => $gapId,
                'accion_id' => $accionId
            ]);

            $this->clearCache($gapId);

            return $accionId;
        });
    }

    public function completarAccion(int $accionId): bool
    {
        return $this->transaction(function() use ($accionId) {
            
            $result = $this->accionModel->completar($accionId);

            if ($result) {
                LogService::audit('Action completed', [
                    'accion_id' => $accionId
                ]);

                $accion = $this->accionModel->find($accionId);
                if ($accion) {
                    $this->clearCache($accion['gap_id']);
                }
            }

            return $result;
        });
    }

    public function deleteGap(int $gapId): bool
    {
        return $this->transaction(function() use ($gapId) {
            
            $result = $this->modelInstance->softDelete($gapId);

            if ($result) {
                LogService::audit('GAP soft deleted', [
                    'gap_id' => $gapId
                ]);

                $this->clearCache($gapId);
            }

            return $result;
        });
    }

    public function getAllWithControles(array $filters = []): array
    {
        $gaps = $this->modelInstance->getAllWithControles($filters);

        return array_map(function($gap) {
            $gap['avance'] = $this->modelInstance->calcularAvance($gap['id']);
            return $gap;
        }, $gaps);
    }

    public function getEstadisticas(): array
    {
        $cacheKey = $this->getCacheKey('stats', 'general');

        return $this->cache->remember($cacheKey, function() {
            return $this->modelInstance->getEstadisticas();
        }, 300);
    }

    public function getGapsCriticos(): array
    {
        return $this->modelInstance->getGapsCriticos();
    }

    public function getAccionesVencidas(): array
    {
        return $this->accionModel->getVencidas();
    }

    public function getEstadisticasAcciones(): array
    {
        return $this->accionModel->getEstadisticas();
    }

    public function validarSOA(int $soaId): array
    {
        $soa = $this->soaModel->find($soaId);

        if (!$soa) {
            return ['valid' => false, 'error' => 'Control no encontrado'];
        }

        if (!$soa['aplicable']) {
            return ['valid' => false, 'error' => 'No se pueden crear GAPs en controles no aplicables'];
        }

        if ($soa['estado'] === 'implementado') {
            return ['valid' => false, 'error' => 'No se pueden crear GAPs en controles implementados'];
        }

        return ['valid' => true];
    }

    protected function clearCache(?int $id = null): void
    {
        parent::clearCache($id);
        $this->cache->delete($this->getCacheKey('stats', 'general'));
    }
}
