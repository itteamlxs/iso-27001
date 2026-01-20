<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Brechas Críticas -->
    <?php if (!empty($gaps_criticos)): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Brechas Críticas</h2>
        <div class="space-y-3">
            <?php foreach (array_slice($gaps_criticos, 0, 5) as $gap): ?>
            <div class="flex items-start p-3 bg-red-50 border border-red-200 rounded-lg">
                <svg class="w-5 h-5 text-red-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($gap['control_nombre']) ?></p>
                    <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($gap['descripcion'] ?? '') ?></p>
                </div>
                <a href="/gap/<?= $gap['id'] ?>" class="text-sm text-blue-600 hover:text-blue-700">Ver</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Evidencias Pendientes -->
    <?php if (!empty($evidencias_pendientes)): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Evidencias Pendientes</h2>
        <div class="space-y-3">
            <?php foreach (array_slice($evidencias_pendientes, 0, 5) as $evidencia): ?>
            <div class="flex items-start p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($evidencia['titulo']) ?></p>
                    <p class="text-xs text-gray-600 mt-1">Estado: <?= htmlspecialchars($evidencia['estado']) ?></p>
                </div>
                <a href="/evidencias/<?= $evidencia['id'] ?>" class="text-sm text-blue-600 hover:text-blue-700">Ver</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
