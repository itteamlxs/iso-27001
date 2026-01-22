<?php

namespace App\Controllers;

use App\Controllers\Base\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Repositories\ControlRepository;
use App\Services\LogService;

class ControlController extends Controller
{
    private ControlRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new ControlRepository();
    }

    public function index(Request $request)
    {
        $this->requireAuth();

        $filters = [
            'dominio_id' => $request->query('dominio_id'),
            'aplicable' => $request->query('aplicable'),
            'estado' => $request->query('estado')
        ];

        $filters = array_filter($filters, fn($value) => $value !== null && $value !== '');

        $controles = $this->repository->getControlesConSOA($filters);
        $dominios = $this->repository->getDominios();
        $stats = $this->repository->getEstadisticasGenerales();

        if ($request->wantsJson()) {
            return $this->json([
                'controles' => $controles,
                'stats' => $stats,
                'filters' => $filters
            ]);
        }

        $content = $this->view('controles.index', [
            'controles' => $controles,
            'dominios' => $dominios,
            'stats' => $stats,
            'filters' => $filters,
            'user' => $this->user()
        ]);

        return $this->layout('app', $content, [
            'title' => 'Controles ISO 27001',
            'user' => $this->user()
        ]);
    }

    public function show(Request $request)
    {
        $this->requireAuth();

        $controlId = (int) $request->param('id');

        $control = $this->repository->getControlConSOA($controlId);

        if (!$control) {
            if ($request->wantsJson()) {
                return $this->error('Control no encontrado', 404);
            }

            $this->flashError('Control no encontrado');
            $this->redirect('/controles');
            return;
        }

        if ($request->wantsJson()) {
            return $this->json(['control' => $control]);
        }

        $content = $this->view('controles.show', [
            'control' => $control,
            'user' => $this->user(),
            'errors' => $this->errors(),
            'old' => Session::get('_old', [])
        ]);

        return $this->layout('app', $content, [
            'title' => 'Control ' . $control['codigo'],
            'user' => $this->user()
        ]);
    }

    public function evaluate(Request $request)
    {
        $this->requireAuth();
        // $this->authorize('controles.edit');

        $controlId = (int) $request->param('id');

        $control = $this->repository->getControlConSOA($controlId);

        if (!$control || !isset($control['soa_id'])) {
            if ($request->wantsJson()) {
                return $this->error('Control no encontrado', 404);
            }

            $this->flashError('Control no encontrado');
            $this->redirect('/controles');
            return;
        }

        $data = $this->validate($request, [
            'aplicable' => 'required',
            'estado' => 'required|in:no_implementado,parcial,implementado',
            'justificacion_no_aplicable' => '',
            'notas' => ''
        ]);

        $data['aplicable'] = (int) $data['aplicable'];

        $validacionAplicabilidad = $this->repository->validarAplicabilidad(
            $control['soa_id'],
            $data['aplicable'],
            $data['justificacion_no_aplicable'] ?? null
        );

        if (!$validacionAplicabilidad['valid']) {
            if ($request->wantsJson()) {
                return $this->error($validacionAplicabilidad['error'], 422);
            }

            $this->flashError($validacionAplicabilidad['error']);
            Session::flash('old', $request->all());
            $this->redirect('/controles/' . $controlId);
            return;
        }

        $validacionEstado = $this->repository->validarEstado(
            $data['aplicable'],
            $data['estado']
        );

        if (!$validacionEstado['valid']) {
            if ($request->wantsJson()) {
                return $this->error($validacionEstado['error'], 422);
            }

            $this->flashError($validacionEstado['error']);
            Session::flash('old', $request->all());
            $this->redirect('/controles/' . $controlId);
            return;
        }

        $user = $this->user();
        $success = $this->repository->evaluarControl(
            $control['soa_id'],
            $data,
            $user['id']
        );

        if (!$success) {
            if ($request->wantsJson()) {
                return $this->error('Error al evaluar el control', 500);
            }

            $this->flashError('Error al evaluar el control');
            $this->redirect('/controles/' . $controlId);
            return;
        }

        LogService::info('Control evaluated', [
            'control_id' => $controlId,
            'soa_id' => $control['soa_id'],
            'aplicable' => $data['aplicable'],
            'estado' => $data['estado'],
            'user_id' => $user['id']
        ]);

        if ($request->wantsJson()) {
            return $this->success('Control evaluado exitosamente');
        }

        $this->flashSuccess('Control evaluado exitosamente');
        $this->redirect('/controles/' . $controlId);
    }

    public function stats(Request $request)
    {
        $this->requireAuth();

        $resumen = $this->repository->getResumenCumplimiento();

        return $this->json($resumen);
    }

    public function search(Request $request)
    {
        $this->requireAuth();

        $query = $request->query('q', '');

        if (strlen($query) < 2) {
            return $this->json(['results' => []]);
        }

        $results = $this->repository->search($query);

        return $this->json(['results' => $results]);
    }
}
