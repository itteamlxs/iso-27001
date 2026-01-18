<?php

namespace App\Controllers;

use App\Controllers\Base\Controller;
use App\Core\Request;
use App\Repositories\RequerimientoRepository;
use App\Services\LogService;

class RequerimientoController extends Controller
{
    private RequerimientoRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new RequerimientoRepository();
    }

    public function index(Request $request)
    {
        $this->requireAuth();

        $requerimientos = $this->repository->getAllWithDetalle();
        $stats = $this->repository->getEstadisticas();

        if ($request->wantsJson()) {
            return $this->json([
                'requerimientos' => $requerimientos,
                'stats' => $stats
            ]);
        }

        $content = $this->view('requerimientos.index', [
            'requerimientos' => $requerimientos,
            'stats' => $stats,
            'user' => $this->user()
        ]);

        return $this->layout('app', $content, [
            'title' => 'Requerimientos Obligatorios',
            'user' => $this->user()
        ]);
    }

    public function show(Request $request)
    {
        $this->requireAuth();

        $id = (int) $request->param('id');

        $requerimiento = $this->repository->getWithDetalle($id);

        if (!$requerimiento) {
            if ($request->wantsJson()) {
                return $this->error('Requerimiento no encontrado', 404);
            }

            $this->flashError('Requerimiento no encontrado');
            $this->redirect('/requerimientos');
            return;
        }

        if ($request->wantsJson()) {
            return $this->json(['requerimiento' => $requerimiento]);
        }

        $content = $this->view('requerimientos.show', [
            'requerimiento' => $requerimiento,
            'user' => $this->user()
        ]);

        return $this->layout('app', $content, [
            'title' => 'Requerimiento ' . $requerimiento['numero'],
            'user' => $this->user()
        ]);
    }

    public function verificar(Request $request)
    {
        $this->requireAuth();

        $id = (int) $request->param('id');

        try {
            $completado = $this->repository->verificarCompletitud($id);

            $mensaje = $completado 
                ? 'Requerimiento completado automáticamente' 
                : 'Requerimiento aún no cumple los criterios de completitud';

            if ($request->wantsJson()) {
                return $this->json([
                    'completado' => $completado,
                    'mensaje' => $mensaje
                ]);
            }

            if ($completado) {
                $this->flashSuccess($mensaje);
            } else {
                $this->flashInfo($mensaje);
            }

            $this->redirect('/requerimientos/' . $id);

        } catch (\Exception $e) {
            LogService::error('Failed to verify requirement', [
                'requerimiento_id' => $id,
                'error' => $e->getMessage()
            ]);

            if ($request->wantsJson()) {
                return $this->error($e->getMessage(), 500);
            }

            $this->flashError('Error al verificar requerimiento');
            $this->back();
        }
    }

    public function verificarTodos(Request $request)
    {
        $this->requireAuth();

        try {
            $resultados = $this->repository->verificarTodosLosRequerimientos();

            $completados = count(array_filter($resultados));
            $total = count($resultados);

            $mensaje = "Verificación completada: {$completados} de {$total} requerimientos están completos";

            if ($request->wantsJson()) {
                return $this->json([
                    'resultados' => $resultados,
                    'completados' => $completados,
                    'total' => $total
                ]);
            }

            $this->flashSuccess($mensaje);
            $this->redirect('/requerimientos');

        } catch (\Exception $e) {
            LogService::error('Failed to verify all requirements', [
                'error' => $e->getMessage()
            ]);

            if ($request->wantsJson()) {
                return $this->error($e->getMessage(), 500);
            }

            $this->flashError('Error al verificar requerimientos');
            $this->back();
        }
    }
}
