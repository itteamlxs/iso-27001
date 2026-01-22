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

        echo $this->layout('app', $content, [
            'title' => 'Controles ISO 27001',
            'user' => $this->user()
        ]);
    }

    public function show(Request $request)
    {
        $this->requireAuth();

        $controlId = (int) $request->param('id');

        // Obtener control con validación IDOR implícita en repository
        $control = $this->repository->getControlConSOA($controlId);

        if (!$control) {
            LogService::warning('Control not found or IDOR attempt', [
                'control_id' => $controlId,
                'user_id' => $this->user()['id'],
                'empresa_id' => Session::get('empresa_id'),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return $this->error('Control no encontrado', 404);
            }

            $this->flashError('Control no encontrado o no tiene acceso');
            $this->redirect('/controles');
            return;
        }

        // Validación adicional: verificar que SOA existe y pertenece al tenant
        if (!isset($control['soa_id']) || empty($control['soa_id'])) {
            LogService::error('SOA entry missing for control', [
                'control_id' => $controlId,
                'empresa_id' => Session::get('empresa_id')
            ]);

            if ($request->wantsJson()) {
                return $this->error('Configuración de control inválida', 500);
            }

            $this->flashError('Error en la configuración del control');
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

        echo $this->layout('app', $content, [
            'title' => 'Control ' . $control['codigo'],
            'user' => $this->user()
        ]);
    }

    public function evaluate(Request $request)
    {
        $this->requireAuth();
        $this->authorize('controles.edit');

        $controlId = (int) $request->param('id');

        // Validación IDOR: Obtener control con SOA del tenant
        $control = $this->repository->getControlConSOA($controlId);

        if (!$control || !isset($control['soa_id'])) {
            LogService::warning('IDOR attempt on control evaluation', [
                'control_id' => $controlId,
                'user_id' => $this->user()['id'],
                'empresa_id' => Session::get('empresa_id'),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return $this->error('Control no encontrado', 404);
            }

            $this->flashError('Control no encontrado o acceso denegado');
            $this->redirect('/controles');
            return;
        }

        // Validación de datos de entrada
        $data = $this->validate($request, [
            'aplicable' => 'required|in:0,1',
            'estado' => 'required|in:no_implementado,parcial,implementado',
            'justificacion_no_aplicable' => '',
            'notas' => ''
        ]);

        $data['aplicable'] = (int) $data['aplicable'];

        // Validación de reglas de negocio: aplicabilidad
        $validacionAplicabilidad = $this->repository->validarAplicabilidad(
            $control['soa_id'],
            $data['aplicable'],
            $data['justificacion_no_aplicable'] ?? null
        );

        if (!$validacionAplicabilidad['valid']) {
            LogService::info('Control evaluation validation failed - aplicabilidad', [
                'control_id' => $controlId,
                'soa_id' => $control['soa_id'],
                'error' => $validacionAplicabilidad['error']
            ]);

            if ($request->wantsJson()) {
                return $this->error($validacionAplicabilidad['error'], 422);
            }

            $this->flashError($validacionAplicabilidad['error']);
            Session::flash('_old', $request->all());
            $this->redirect('/controles/' . $controlId);
            return;
        }

        // Validación de reglas de negocio: estado
        $validacionEstado = $this->repository->validarEstado(
            $data['aplicable'],
            $data['estado']
        );

        if (!$validacionEstado['valid']) {
            LogService::info('Control evaluation validation failed - estado', [
                'control_id' => $controlId,
                'soa_id' => $control['soa_id'],
                'error' => $validacionEstado['error']
            ]);

            if ($request->wantsJson()) {
                return $this->error($validacionEstado['error'], 422);
            }

            $this->flashError($validacionEstado['error']);
            Session::flash('_old', $request->all());
            $this->redirect('/controles/' . $controlId);
            return;
        }

        // Ejecutar evaluación con transacción
        $user = $this->user();
        $success = $this->repository->evaluarControl(
            $control['soa_id'],
            $data,
            $user['id']
        );

        if (!$success) {
            LogService::error('Control evaluation failed', [
                'control_id' => $controlId,
                'soa_id' => $control['soa_id'],
                'user_id' => $user['id']
            ]);

            if ($request->wantsJson()) {
                return $this->error('Error al evaluar el control', 500);
            }

            $this->flashError('Error al evaluar el control. Intente nuevamente.');
            $this->redirect('/controles/' . $controlId);
            return;
        }

        // Logging exitoso con detalles
        LogService::info('Control evaluated successfully', [
            'control_id' => $controlId,
            'control_codigo' => $control['codigo'],
            'soa_id' => $control['soa_id'],
            'aplicable' => $data['aplicable'],
            'estado' => $data['estado'],
            'user_id' => $user['id'],
            'empresa_id' => Session::get('empresa_id')
        ]);

        if ($request->wantsJson()) {
            return $this->success('Control evaluado exitosamente', [
                'control' => [
                    'id' => $controlId,
                    'codigo' => $control['codigo'],
                    'aplicable' => $data['aplicable'],
                    'estado' => $data['estado']
                ]
            ]);
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
