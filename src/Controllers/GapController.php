<?php

namespace App\Controllers;

use App\Controllers\Base\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Repositories\GapRepository;
use App\Repositories\ControlRepository;
use App\Services\LogService;

class GapController extends Controller
{
    private GapRepository $repository;
    private ControlRepository $controlRepository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new GapRepository();
        $this->controlRepository = new ControlRepository();
    }

    public function index(Request $request)
    {
        $this->requireAuth();

        $filters = [
            'prioridad' => $request->query('prioridad'),
            'dominio_id' => $request->query('dominio_id')
        ];

        $filters = array_filter($filters);

        $gaps = $this->repository->getAllWithControles($filters);
        $stats = $this->repository->getEstadisticas();
        $dominios = $this->controlRepository->getDominios();

        if ($request->wantsJson()) {
            return $this->json([
                'gaps' => $gaps,
                'stats' => $stats
            ]);
        }

        $content = $this->view('gap.index', [
            'gaps' => $gaps,
            'stats' => $stats,
            'dominios' => $dominios,
            'filters' => $filters,
            'user' => $this->user()
        ]);

        return $this->layout('app', $content, [
            'title' => 'Análisis de Brechas (GAP)',
            'user' => $this->user()
        ]);
    }

    public function create(Request $request)
    {
        $this->requireAuth();
        $this->authorize('gap.create');

        $controles = $this->controlRepository->getControlesParaGAP();

        $content = $this->view('gap.create', [
            'controles' => $controles,
            'errors' => $this->errors(),
            'old' => Session::get('_old', []),
            'user' => $this->user()
        ]);

        return $this->layout('app', $content, [
            'title' => 'Crear GAP',
            'user' => $this->user()
        ]);
    }

    public function store(Request $request)
    {
        $this->requireAuth();
        $this->authorize('gap.create');

        $data = $this->validate($request, [
            'soa_id' => 'required|integer',
            'brecha' => 'required|min:10',
            'objetivo' => 'required|min:10',
            'prioridad' => 'required|in:alta,media,baja',
            'responsable' => 'required|min:3',
            'fecha_compromiso' => 'required|date',
            'acciones' => 'required'
        ]);

        $validacion = $this->repository->validarSOA((int) $data['soa_id']);

        if (!$validacion['valid']) {
            if ($request->wantsJson()) {
                return $this->error($validacion['error'], 422);
            }

            $this->flashError($validacion['error']);
            Session::flash('old', $request->all());
            $this->back();
            return;
        }

        $acciones = json_decode($data['acciones'], true);

        if (!$acciones || count($acciones) === 0) {
            if ($request->wantsJson()) {
                return $this->error('Debe agregar al menos una acción', 422);
            }

            $this->flashError('Debe agregar al menos una acción');
            Session::flash('old', $request->all());
            $this->back();
            return;
        }

        unset($data['acciones']);

        try {
            $gapId = $this->repository->createWithAcciones($data, $acciones);

            if ($request->wantsJson()) {
                return $this->success('GAP creado exitosamente', ['gap_id' => $gapId]);
            }

            $this->flashSuccess('GAP creado exitosamente');
            $this->redirect('/gap/' . $gapId);

        } catch (\Exception $e) {
            LogService::error('Failed to create GAP', [
                'error' => $e->getMessage()
            ]);

            if ($request->wantsJson()) {
                return $this->error('Error al crear GAP', 500);
            }

            $this->flashError('Error al crear GAP');
            Session::flash('old', $request->all());
            $this->back();
        }
    }

    public function show(Request $request)
    {
        $this->requireAuth();

        $gapId = (int) $request->param('id');
        $gap = $this->repository->getWithAcciones($gapId);

        if (!$gap) {
            if ($request->wantsJson()) {
                return $this->error('GAP no encontrado', 404);
            }

            $this->flashError('GAP no encontrado');
            $this->redirect('/gap');
            return;
        }

        if ($request->wantsJson()) {
            return $this->json(['gap' => $gap]);
        }

        $content = $this->view('gap.show', [
            'gap' => $gap,
            'user' => $this->user()
        ]);

        return $this->layout('app', $content, [
            'title' => 'GAP - ' . $gap['control_codigo'],
            'user' => $this->user()
        ]);
    }

    public function addAction(Request $request)
    {
        $this->requireAuth();
        $this->authorize('gap.edit');

        $gapId = (int) $request->param('id');

        $data = $this->validate($request, [
            'descripcion' => 'required|min:10',
            'responsable' => 'required|min:3',
            'fecha_compromiso' => 'required|date'
        ]);

        try {
            $accionId = $this->repository->addAccion($gapId, $data);

            if ($request->wantsJson()) {
                return $this->success('Acción agregada', ['accion_id' => $accionId]);
            }

            $this->flashSuccess('Acción agregada exitosamente');
            $this->redirect('/gap/' . $gapId);

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getMessage(), 400);
            }

            $this->flashError($e->getMessage());
            $this->back();
        }
    }

    public function completeAction(Request $request)
    {
        $this->requireAuth();
        $this->authorize('gap.edit');

        $gapId = (int) $request->param('id');

        $data = $this->validate($request, [
            'accion_id' => 'required|integer'
        ]);

        try {
            $result = $this->repository->completarAccion((int) $data['accion_id']);

            if (!$result) {
                throw new \Exception('No se pudo completar la acción');
            }

            if ($request->wantsJson()) {
                return $this->success('Acción completada');
            }

            $this->flashSuccess('Acción completada exitosamente');
            $this->redirect('/gap/' . $gapId);

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getMessage(), 400);
            }

            $this->flashError($e->getMessage());
            $this->back();
        }
    }

    public function delete(Request $request)
    {
        $this->requireAuth();
        $this->authorize('gap.delete');

        $gapId = (int) $request->param('id');

        try {
            $result = $this->repository->deleteGap($gapId);

            if (!$result) {
                throw new \Exception('No se pudo eliminar el GAP');
            }

            if ($request->wantsJson()) {
                return $this->success('GAP eliminado');
            }

            $this->flashSuccess('GAP eliminado exitosamente');
            $this->redirect('/gap');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getMessage(), 400);
            }

            $this->flashError($e->getMessage());
            $this->back();
        }
    }
}
