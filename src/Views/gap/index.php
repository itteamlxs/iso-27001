<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Análisis de Brechas (GAP Analysis)</h1>
        <p class="mt-1 text-sm text-gray-600">Identifica las brechas entre el estado actual y los requisitos ISO 27001</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Brechas</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['total'] ?? 0 ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Críticas</p>
                    <p class="text-2xl font-bold text-red-600 mt-1"><?= $stats['criticas'] ?? 0 ?></p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Altas</p>
                    <p class="text-2xl font-bold text-orange-600 mt-1"><?= $stats['altas'] ?? 0 ?></p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Medias/Bajas</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1"><?= $stats['medias'] ?? 0 ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <form method="GET" action="/gap" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="severidad" class="block text-sm font-medium text-gray-700 mb-1">Severidad</label>
                <select 
                    name="severidad" 
                    id="severidad"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Todas</option>
                    <option value="critica" <?= ($_GET['severidad'] ?? '') == 'critica' ? 'selected' : '' ?>>Crítica</option>
                    <option value="alta" <?= ($_GET['severidad'] ?? '') == 'alta' ? 'selected' : '' ?>>Alta</option>
                    <option value="media" <?= ($_GET['severidad'] ?? '') == 'media' ? 'selected' : '' ?>>Media</option>
                    <option value="baja" <?= ($_GET['severidad'] ?? '') == 'baja' ? 'selected' : '' ?>>Baja</option>
                </select>
            </div>

            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select 
                    name="estado" 
                    id="estado"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Todos</option>
                    <option value="identificado" <?= ($_GET['estado'] ?? '') == 'identificado' ? 'selected' : '' ?>>Identificado</option>
                    <option value="en_progreso" <?= ($_GET['estado'] ?? '') == 'en_progreso' ? 'selected' : '' ?>>En Progreso</option>
                    <option value="resuelto" <?= ($_GET['estado'] ?? '') == 'resuelto' ? 'selected' : '' ?>>Resuelto</option>
                </select>
            </div>

            <div class="flex items-end">
                <button 
                    type="submit"
                    class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                >
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Lista de Brechas -->
    <div class="space-y-4">
        <?php if (empty($gaps)): ?>
            <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay brechas identificadas</h3>
                <p class="mt-1 text-sm text-gray-500">Comienza evaluando los controles para identificar brechas</p>
                <div class="mt-6">
                    <a href="/controles" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Ir a Controles
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($gaps as $gap): ?>
                <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-sm font-mono font-semibold text-blue-600">
                                    <?= htmlspecialchars($gap['control_codigo']) ?>
                                </span>
                                
                                <?php
                                $severidades = [
                                    'critica' => ['text' => 'Crítica', 'class' => 'bg-red-100 text-red-800'],
                                    'alta' => ['text' => 'Alta', 'class' => 'bg-orange-100 text-orange-800'],
                                    'media' => ['text' => 'Media', 'class' => 'bg-yellow-100 text-yellow-800'],
                                    'baja' => ['text' => 'Baja', 'class' => 'bg-green-100 text-green-800']
                                ];
                                $sevInfo = $severidades[$gap['severidad']] ?? ['text' => $gap['severidad'], 'class' => 'bg-gray-100 text-gray-800'];
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $sevInfo['class'] ?>">
                                    <?= $sevInfo['text'] ?>
                                </span>

                                <?php
                                $estados = [
                                    'identificado' => ['text' => 'Identificado', 'class' => 'bg-blue-100 text-blue-800'],
                                    'en_progreso' => ['text' => 'En Progreso', 'class' => 'bg-yellow-100 text-yellow-800'],
                                    'resuelto' => ['text' => 'Resuelto', 'class' => 'bg-green-100 text-green-800']
                                ];
                                $estInfo = $estados[$gap['estado']] ?? ['text' => $gap['estado'], 'class' => 'bg-gray-100 text-gray-800'];
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $estInfo['class'] ?>">
                                    <?= $estInfo['text'] ?>
                                </span>
                            </div>

                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                <?= htmlspecialchars($gap['control_nombre']) ?>
                            </h3>

                            <p class="text-sm text-gray-700 mb-3">
                                <span class="font-medium">Brecha:</span> 
                                <?= htmlspecialchars($gap['descripcion']) ?>
                            </p>

                            <?php if (!empty($gap['impacto'])): ?>
                                <p class="text-sm text-gray-700 mb-3">
                                    <span class="font-medium">Impacto:</span> 
                                    <?= htmlspecialchars($gap['impacto']) ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($gap['fecha_objetivo'])): ?>
                                <div class="flex items-center gap-4 text-sm text-gray-500">
                                    <div class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>Objetivo: <?= date('d/m/Y', strtotime($gap['fecha_objetivo'])) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="ml-4">
                            <a 
                                href="/gap/<?= $gap['id'] ?>" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
                            >
                                Ver Detalle
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Botón Crear -->
    <div class="flex justify-end">
        <a 
            href="/gap/create" 
            class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-lg transition-colors"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nueva Brecha
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const severidadSelect = document.getElementById('severidad');
    const estadoSelect = document.getElementById('estado');

    [severidadSelect, estadoSelect].forEach(el => {
        el?.addEventListener('change', function() {
            this.form.submit();
        });
    });
});
</script>
