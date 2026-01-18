<?php

namespace App\Repositories;

use App\Repositories\Base\Repository;
use App\Models\Evidencia;
use App\Models\SOA;
use App\Services\FileService;
use App\Services\LogService;

class EvidenciaRepository extends Repository
{
    protected string $model = Evidencia::class;
    private FileService $fileService;
    private SOA $soaModel;

    public function __construct()
    {
        parent::__construct();
        $this->fileService = new FileService();
        $this->soaModel = new SOA();
    }

    public function upload(int $controlId, string $tipo, string $descripcion, array $file): int
    {
        return $this->transaction(function() use ($controlId, $tipo, $descripcion, $file) {
            
            $soa = $this->soaModel->findByControl($controlId);
            
            if (!$soa || !$soa['aplicable']) {
                throw new \InvalidArgumentException('Solo se pueden subir evidencias en controles aplicables');
            }

            $empresaId = $this->tenant->requireTenant();
            
            $uploadResult = $this->fileService->upload($file, $empresaId, 'evidencias');

            if ($this->modelInstance->hashExists($uploadResult['hash'])) {
                $this->fileService->delete($uploadResult['path']);
                throw new \InvalidArgumentException('Este archivo ya fue subido anteriormente');
            }

            $evidenciaId = $this->create([
                'control_id' => $controlId,
                'tipo' => $tipo,
                'descripcion' => $descripcion,
                'archivo' => $uploadResult['filename'],
                'nombre_original' => $uploadResult['original_name'],
                'ruta' => $uploadResult['path'],
                'tamanio' => $uploadResult['size'],
                'mime_type' => $uploadResult['mime_type'],
                'hash' => $uploadResult['hash'],
                'estado_validacion' => 'pendiente'
            ]);

            LogService::audit('Evidence uploaded', [
                'evidencia_id' => $evidenciaId,
                'control_id' => $controlId,
                'filename' => $uploadResult['filename'],
                'size' => $uploadResult['size']
            ]);

            $this->clearCache();

            return $evidenciaId;
        });
    }

    public function validar(int $evidenciaId, bool $aprobada, int $validadorId, ?string $comentarios = null): bool
    {
        return $this->transaction(function() use ($evidenciaId, $aprobada, $validadorId, $comentarios) {
            
            $evidencia = $this->find($evidenciaId);
            
            if (!$evidencia) {
                throw new \InvalidArgumentException('Evidencia no encontrada');
            }

            if ($evidencia['estado_validacion'] !== 'pendiente') {
                throw new \InvalidArgumentException('Esta evidencia ya fue validada');
            }

            $result = $this->modelInstance->validar($evidenciaId, $aprobada, $validadorId, $comentarios);

            if ($result) {
                LogService::audit('Evidence validated', [
                    'evidencia_id' => $evidenciaId,
                    'control_id' => $evidencia['control_id'],
                    'aprobada' => $aprobada,
                    'validado_por' => $validadorId
                ]);

                $this->clearCache($evidenciaId);
            }

            return $result;
        });
    }

    public function getWithControl(int $evidenciaId): ?array
    {
        return $this->modelInstance->findWithControl($evidenciaId);
    }

    public function getAllWithControles(array $filters = []): array
    {
        return $this->modelInstance->getAllWithControles($filters);
    }

    public function getByControl(int $controlId): array
    {
        return $this->modelInstance->findByControl($controlId);
    }

    public function download(int $evidenciaId): array
    {
        $evidencia = $this->find($evidenciaId);

        if (!$evidencia) {
            throw new \InvalidArgumentException('Evidencia no encontrada');
        }

        $fullPath = $this->fileService->getFullPath($evidencia['ruta']);

        if (!file_exists($fullPath)) {
            throw new \RuntimeException('Archivo no encontrado en el servidor');
        }

        LogService::info('Evidence downloaded', [
            'evidencia_id' => $evidenciaId,
            'control_id' => $evidencia['control_id'],
            'user_id' => \App\Core\Session::get('user_id')
        ]);

        return [
            'path' => $fullPath,
            'filename' => $evidencia['nombre_original'],
            'mime_type' => $evidencia['mime_type']
        ];
    }

    public function deleteEvidencia(int $evidenciaId): bool
    {
        return $this->transaction(function() use ($evidenciaId) {
            
            $evidencia = $this->find($evidenciaId);
            
            if (!$evidencia) {
                throw new \InvalidArgumentException('Evidencia no encontrada');
            }

            $this->fileService->delete($evidencia['ruta']);

            $result = $this->delete($evidenciaId);

            if ($result) {
                LogService::audit('Evidence deleted', [
                    'evidencia_id' => $evidenciaId,
                    'control_id' => $evidencia['control_id']
                ]);

                $this->clearCache($evidenciaId);
            }

            return $result;
        });
    }

    public function getEstadisticas(): array
    {
        $cacheKey = $this->getCacheKey('stats', 'general');

        return $this->cache->remember($cacheKey, function() {
            return $this->modelInstance->getEstadisticas();
        }, 300);
    }

    public function getPendientes(): array
    {
        return $this->modelInstance->getPendientes();
    }

    public function getTipos(): array
    {
        return $this->modelInstance->getTipos();
    }

    public function validarControl(int $controlId): array
    {
        $soa = $this->soaModel->findByControl($controlId);

        if (!$soa) {
            return ['valid' => false, 'error' => 'Control no encontrado'];
        }

        if (!$soa['aplicable']) {
            return ['valid' => false, 'error' => 'No se pueden subir evidencias en controles no aplicables'];
        }

        return ['valid' => true];
    }

    protected function clearCache(?int $id = null): void
    {
        parent::clearCache($id);
        $this->cache->delete($this->getCacheKey('stats', 'general'));
    }
}
