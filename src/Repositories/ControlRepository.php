<?php

namespace App\Repositories;

use App\Repositories\Base\Repository;
use App\Models\Control;
use App\Models\SOA;
use App\Services\LogService;

class ControlRepository extends Repository
{
    protected string $model = Control::class;
    private SOA $soaModel;

    public function __construct()
    {
        parent::__construct();
        $this->soaModel = new SOA();
    }

    public function getControlesConSOA(array $filters = []): array
    {
        return $this->soaModel->getAllWithControles($filters);
    }

    public function getControlConSOA(int $controlId): ?array
    {
        $control = $this->modelInstance->findWithDominio($controlId);

        if (!$control) {
            return null;
        }

        $soa = $this->soaModel->findByControl($controlId);

        if (!$soa) {
            LogService::warning('SOA entry not found for control', [
                'control_id' => $controlId,
                'empresa_id' => $this->tenant->getTenant()
            ]);
            return null;
        }

        return array_merge($control, [
            'soa_id' => $soa['id'],
            'aplicable' => $soa['aplicable'],
            'estado' => $soa['estado'],
            'justificacion_no_aplicable' => $soa['justificacion_no_aplicable'],
            'fecha_evaluacion' => $soa['fecha_evaluacion'],
            'evaluado_por' => $soa['evaluado_por'],
            'notas' => $soa['notas']
        ]);
    }

    public function evaluarControl(int $soaId, array $data, int $evaluadorId): bool
    {
        return $this->transaction(function() use ($soaId, $data, $evaluadorId) {
            
            // Validar que SOA existe y pertenece al tenant actual
            $soa = $this->soaModel->find($soaId);
            
            if (!$soa) {
                LogService::error('SOA not found during evaluation', [
                    'soa_id' => $soaId,
                    'empresa_id' => $this->tenant->getTenant()
                ]);
                throw new \RuntimeException('Registro SOA no encontrado');
            }

            // Ejecutar evaluación
            $result = $this->soaModel->evaluate($soaId, $data, $evaluadorId);

            if ($result) {
                // Invalidar caché después de evaluación exitosa
                $this->clearCache();
                
                LogService::info('Control evaluated successfully in repository', [
                    'soa_id' => $soaId,
                    'control_id' => $soa['control_id'],
                    'aplicable' => $data['aplicable'],
                    'estado' => $data['estado'] ?? null,
                    'evaluador_id' => $evaluadorId
                ]);
            }

            return $result;
        });
    }

    public function getDominios(): array
    {
        $cacheKey = 'dominios:all';

        return $this->cache->remember($cacheKey, function() {
            return $this->modelInstance->getDominios();
        }, 3600);
    }

    public function getEstadisticasGenerales(): array
    {
        $cacheKey = $this->getCacheKey('stats', 'general');

        return $this->cache->remember($cacheKey, function() {
            $stats = $this->soaModel->getEstadisticas();
            $cumplimiento = $this->soaModel->getCumplimiento();

            return array_merge($stats, ['cumplimiento' => $cumplimiento]);
        }, 300);
    }

    public function getEstadisticasPorDominio(): array
    {
        $cacheKey = $this->getCacheKey('stats', 'por_dominio');

        return $this->cache->remember($cacheKey, function() {
            return $this->soaModel->getEstadisticasPorDominio();
        }, 300);
    }

    public function getControlesNoImplementados(): array
    {
        return $this->soaModel->getControlesNoImplementados();
    }

    public function getControlesParaGAP(): array
    {
        $controles = $this->getControlesNoImplementados();

        return array_map(function($control) {
            return [
                'soa_id' => $control['id'],
                'control_id' => $control['control_id'],
                'codigo' => $control['codigo'],
                'nombre' => $control['nombre'],
                'dominio' => $control['dominio_nombre'],
                'estado' => $control['estado']
            ];
        }, $controles);
    }

    public function search(string $query): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        return $this->modelInstance->search($query);
    }

    public function validarAplicabilidad(int $soaId, bool $aplicable, ?string $justificacion = null): array
    {
        if (!$aplicable && empty($justificacion)) {
            return [
                'valid' => false,
                'error' => 'Se requiere justificación para marcar un control como no aplicable'
            ];
        }

        if (!$aplicable && strlen($justificacion) < 20) {
            return [
                'valid' => false,
                'error' => 'La justificación debe tener al menos 20 caracteres para ser válida'
            ];
        }

        return ['valid' => true];
    }

    public function validarEstado(bool $aplicable, string $estado): array
    {
        if (!$aplicable && $estado !== 'no_implementado') {
            return [
                'valid' => false,
                'error' => 'Los controles no aplicables deben estar en estado "No implementado"'
            ];
        }

        $estadosValidos = ['no_implementado', 'parcial', 'implementado'];
        if (!in_array($estado, $estadosValidos)) {
            return [
                'valid' => false,
                'error' => 'Estado de implementación no válido. Debe ser: no_implementado, parcial o implementado'
            ];
        }

        return ['valid' => true];
    }

    public function getResumenCumplimiento(): array
    {
        $stats = $this->getEstadisticasGenerales();
        $porDominio = $this->getEstadisticasPorDominio();

        $dominios = [];
        foreach ($porDominio as $dominio) {
            $cumplimientoDominio = $dominio['aplicables'] > 0
                ? round(($dominio['implementados'] / $dominio['aplicables']) * 100, 2)
                : 0;

            $dominios[] = [
                'codigo' => $dominio['codigo'],
                'nombre' => $dominio['nombre'],
                'total' => (int) $dominio['total'],
                'aplicables' => (int) $dominio['aplicables'],
                'implementados' => (int) $dominio['implementados'],
                'parciales' => (int) $dominio['parciales'],
                'cumplimiento' => $cumplimientoDominio
            ];
        }

        return [
            'general' => $stats,
            'dominios' => $dominios
        ];
    }

    protected function clearCache(?int $id = null): void
    {
        parent::clearCache($id);

        $this->cache->delete($this->getCacheKey('stats', 'general'));
        $this->cache->delete($this->getCacheKey('stats', 'por_dominio'));
    }
}
