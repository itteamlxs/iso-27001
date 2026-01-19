<?php

namespace App\Controllers;

use App\Controllers\Base\Controller;
use App\Core\Request;
use App\Services\MetricsService;
use App\Repositories\GapRepository;
use App\Repositories\EvidenciaRepository;

class DashboardController extends Controller
{
    private MetricsService $metricsService;
    private GapRepository $gapRepository;
    private EvidenciaRepository $evidenciaRepository;

    public function __construct()
    {
        parent::__construct();
        $this->metricsService = new MetricsService();
        $this->gapRepository = new GapRepository();
        $this->evidenciaRepository = new EvidenciaRepository();
    }

    public function index(Request $request)
    {
        $this->requireAuth();

        $metrics = $this->metricsService->getDashboardMetrics();
        
        $gapsCriticos = $this->gapRepository->getGapsCriticos();
        $accionesVencidas = $this->gapRepository->getAccionesVencidas();
        $evidenciasPendientes = $this->evidenciaRepository->getPendientes();

        if ($request->wantsJson()) {
            return $this->json([
                'metrics' => $metrics,
                'gaps_criticos' => $gapsCriticos,
                'acciones_vencidas' => $accionesVencidas,
                'evidencias_pendientes' => $evidenciasPendientes
            ]);
        }

        $content = $this->view('dashboard.index', [
            'metrics' => $metrics,
            'gaps_criticos' => $gapsCriticos,
            'acciones_vencidas' => $accionesVencidas,
            'evidencias_pendientes' => $evidenciasPendientes,
            'user' => $this->user()
        ]);

        return $this->layout('app', $content, [
            'title' => 'Dashboard',
            'user' => $this->user()
        ]);
    }

    public function timeline(Request $request)
    {
        $this->requireAuth();

        $dias = (int) $request->query('dias', 30);
        
        if ($dias < 7) $dias = 7;
        if ($dias > 365) $dias = 365;

        $timeline = $this->metricsService->getTimeline($dias);

        return $this->json(['timeline' => $timeline]);
    }

    public function refresh(Request $request)
    {
        $this->requireAuth();

        $this->metricsService->clearCache();

        $metrics = $this->metricsService->getDashboardMetrics();

        return $this->json([
            'success' => true,
            'metrics' => $metrics
        ]);
    }
}
