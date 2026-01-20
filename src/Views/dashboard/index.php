<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900">Panel de Control</h1>
        <p class="text-gray-600 mt-1">Bienvenido, <?= htmlspecialchars($user['nombre']) ?></p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?= component('stats-card', [
            'title' => 'Controles',
            'value' => $metrics['total_controles'] ?? 0,
            'color' => 'blue',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
        ]) ?>

        <?= component('stats-card', [
            'title' => 'Brechas',
            'value' => $metrics['total_gaps'] ?? 0,
            'color' => 'yellow',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>'
        ]) ?>

        <?= component('stats-card', [
            'title' => 'Evidencias',
            'value' => $metrics['total_evidencias'] ?? 0,
            'color' => 'green',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'
        ]) ?>

        <?= component('stats-card', [
            'title' => 'Cumplimiento',
            'value' => round($metrics['porcentaje_cumplimiento'] ?? 0) . '%',
            'color' => 'purple',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'
        ]) ?>
    </div>

    <!-- Quick Actions -->
    <?php include __DIR__ . '/../components/dashboard-actions.php'; ?>

    <!-- Alerts Section -->
    <?php include __DIR__ . '/../components/dashboard-alerts.php'; ?>
</div>
