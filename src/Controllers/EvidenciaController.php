<?php

namespace App\Controllers;

use App\Controllers\Base\Controller;
use App\Core\Request;
use App\Repositories\EvidenciaRepository;
use App\Repositories\ControlRepository;
use App\Middleware\RateLimitMiddleware;
use App\Services\LogService;

class EvidenciaController extends Controller
{
    private EvidenciaRepository $repository;
    private ControlRepository $controlRepository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new EvidenciaRepository();
        $this->controlRepository = new ControlRepository();
    }

    public function index(Request $request)
    {
        $this->requireAuth();

        $filters = [
            'control_id' => $request->query('control_id'),
            'tipo' => $request->query('tipo'),
            'estado_validacion' => $request->query('estado_validacion')
        ];

        $filters = array_filter($filters);

        $evidencias = $this->repository->getAllWithControles($filters);
        $stats = $this->repository->getEstadisticas();
        $tipos = $this->repository->getTipos();

        if ($request->wantsJson()) {
            return $this->json([
                'evidencias' => $evidencias,
                'stats' => $stats
            ]);
        }

        $content = $this->view('evidencias.index', [
            'evidencias' => $evidencias,
            'stats' => $stats,
            'tipos' => $tipos,
            'filters' => $filters,
            'user' => $this->user()
        ]);

        return $this->layout('app', $content, [
            'title' => 'Evidencias',
            'user' => $this->user()
        ]);
    }

    public function create(Request $request)
    {
        $this->requireAuth();
        $this->authorize('evidencias.create');

        $controles = $this->controlRepository->getControlesConSOA([
            'aplicable' => 1
        ]);

        $tipos = $this->repository->getTipos();

        $content = $this->view('evidencias.create', [
            'controles' => $controles,
            'tipos' => $tipos,
            'errors' => $this->errors(),
            'old' => Session::get('_old', []),
            'user' => $this->user()
        ]);

        return $this->layout('app', $content, [
            'title' => 'Subir Evidencia',
            'user' => $this->user()
        ]);
    }

    public function store(Request $request)
    {
        $this->requireAuth();
        $this->authorize('evidencias.create');

        $rateLimiter = new RateLimitMiddleware('upload');
        $rateLimiter->handle($request, function() {});

        $data = $this->validate($request, [
            'control_id' => 'required|integer',
            'tipo' => 'required',
            'descripcion' => 'required|min:10'
        ]);

        if (!$request->hasFile('archivo')) {
            if ($request->wantsJson()) {
                return $this->error('Debe seleccionar un archivo', 422);
            }

            $this->flashError('Debe seleccionar un archivo');
            Session::put('_old', $request->all());
            $this->back();
            return;
        }

        $validacion = $this->repository->validarControl((int) $data['control_id']);

        if (!$validacion['valid']) {
            if ($request->wantsJson()) {
                return $this->error($validacion['error'], 422);
            }

            $this->flashError($validacion['error']);
            Session::put('_old', $request->all());
            $this->back();
            return;
        }

        try {
            $file = $request->file('archivo');

            $evidenciaId = $this->repository->upload(
                (int) $data['control_id'],
                $data['tipo'],
                $data['descripcion'],
                $file
            );

            if ($request->wantsJson()) {
                return $this->success('Evidencia subida exitosamente', [
                    'evidencia_id' => $evidenciaId
                ]);
            }

            $this->flashSuccess('Evidencia subida exitosamente. Pendiente de validación.');
            $this->redirect('/evidencias');

        } catch (\Exception $e) {
            LogService::error('Failed to upload evidence', [
                'error' => $e->getMessage(),
                'control_id' => $data['control_id']
            ]);

            if ($request->wantsJson()) {
                return $this->error($e->getMessage(), 500);
            }

            $this->flashError($e->getMessage());
            Session::put('_old', $request->all());
            $this->back();
        }
    }

    public function download(Request $request)
    {
        $this->requireAuth();

        $rateLimiter = new RateLimitMiddleware('download');
        $rateLimiter->handle($request, function() {});

        $evidenciaId = (int) $request->param('id');

        try {
            $downloadData = $this->repository->download($evidenciaId);

            $response = new \App\Core\Response();
            $response->download($downloadData['path'], $downloadData['filename']);

        } catch (\Exception $e) {
            LogService::warning('Failed to download evidence', [
                'evidencia_id' => $evidenciaId,
                'error' => $e->getMessage(),
                'user_id' => $this->user()['id']
            ]);

            if ($request->wantsJson()) {
                return $this->error($e->getMessage(), 404);
            }

            $this->flashError($e->getMessage());
            $this->back();
        }
    }

    public function validate(Request $request)
    {
        $this->requireAuth();
        $this->authorize('evidencias.validate');

        $evidenciaId = (int) $request->param('id');

        $data = $this->validate($request, [
            'aprobada' => 'required',
            'comentarios' => ''
        ]);

        $aprobada = (bool) $data['aprobada'];
        $comentarios = $data['comentarios'] ?? null;

        try {
            $user = $this->user();
            
            $result = $this->repository->validar(
                $evidenciaId,
                $aprobada,
                $user['id'],
                $comentarios
            );

            if (!$result) {
                throw new \Exception('No se pudo validar la evidencia');
            }

            $mensaje = $aprobada ? 'Evidencia aprobada' : 'Evidencia rechazada';

            if ($request->wantsJson()) {
                return $this->success($mensaje);
            }

            $this->flashSuccess($mensaje);
            $this->back();

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getMessage(), 400);
            }

            $this->flashError($e->getMessage());
            $this->back();
        }
    }
}
