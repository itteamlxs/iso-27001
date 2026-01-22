<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Controles ISO 27001:2022</h1>
            <p class="mt-1 text-sm text-gray-600">Gestiona los 93 controles de seguridad de la información</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-600">Progreso:</span>
            <div class="flex items-center gap-2">
                <?php 
                $porcentaje = $stats['total'] > 0 ? ($stats['implementados'] / $stats['total']) * 100 : 0;
                ?>
                <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-green-500 transition-all" style="width: <?= $porcentaje ?>%"></div>
                </div>
                <span class="text-sm font-semibold text-gray-900"><?= number_format($porcentaje, 1) ?>%</span>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Controles</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['total'] ?? 0 ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Implementados</p>
                    <p class="text-2xl font-bold text-green-600 mt-1"><?= $stats['implementados'] ?? 0 ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">En Progreso</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1"><?= $stats['parcial'] ?? 0 ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">No Implementados</p>
                    <p class="text-2xl font-bold text-red-600 mt-1"><?= $stats['no_implementados'] ?? 0 ?></p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <form method="GET" action="/controles" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Filtro Dominio -->
            <div>
                <label for="dominio_id" class="block text-sm font-medium text-gray-700 mb-1">Dominio</label>
                <select 
                    name="dominio_id" 
                    id="dominio_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Todos</option>
                    <?php foreach ($dominios as $dominio): ?>
                        <option value="<?= $dominio['id'] ?>" <?= ($filters['dominio_id'] ?? '') == $dominio['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dominio['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtro Aplicable -->
            <div>
                <label for="aplicable" class="block text-sm font-medium text-gray-700 mb-1">Aplicable</label>
                <select 
                    name="aplicable" 
                    id="aplicable"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Todos</option>
                    <option value="1" <?= ($filters['aplicable'] ?? '') == '1' ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= ($filters['aplicable'] ?? '') == '0' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <!-- Filtro Estado -->
            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select 
                    name="estado" 
                    id="estado"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Todos</option>
                    <option value="implementado" <?= ($filters['estado'] ?? '') == 'implementado' ? 'selected' : '' ?>>Implementado</option>
                    <option value="parcial" <?= ($filters['estado'] ?? '') == 'parcial' ? 'selected' : '' ?>>Parcial</option>
                    <option value="no_implementado" <?= ($filters['estado'] ?? '') == 'no_implementado' ? 'selected' : '' ?>>No Implementado</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Lista de Controles -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Control</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dominio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aplicable</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($controles)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="mt-2 text-sm">No se encontraron controles</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($controles as $control): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-mono font-medium text-gray-900"><?= htmlspecialchars($control['codigo']) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 font-medium"><?= htmlspecialchars($control['nombre']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-600"><?= htmlspecialchars($control['dominio_nombre']) ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $estado = $control['estado'];
                                    $estados = [
                                        'implementado' => ['text' => 'Implementado', 'class' => 'bg-green-100 text-green-800'],
                                        'parcial' => ['text' => 'Parcial', 'class' => 'bg-yellow-100 text-yellow-800'],
                                        'no_implementado' => ['text' => 'No Implementado', 'class' => 'bg-red-100 text-red-800']
                                    ];
                                    $estadoInfo = $estados[$estado] ?? ['text' => $estado, 'class' => 'bg-gray-100 text-gray-800'];
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $estadoInfo['class'] ?>">
                                        <?= $estadoInfo['text'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($control['aplicable']): ?>
                                        <span class="text-green-600 flex items-center gap-1">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-sm">Sí</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400 flex items-center gap-1">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-sm">No</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="/controles/<?= $control['control_id'] ?>" class="text-blue-600 hover:text-blue-900">
                                        Ver detalle →
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dominioSelect = document.getElementById('dominio_id');
    const aplicableSelect = document.getElementById('aplicable');
    const estadoSelect = document.getElementById('estado');

    [dominioSelect, aplicableSelect, estadoSelect].forEach(el => {
        el?.addEventListener('change', function() {
            this.form.submit();
        });
    });
});
</script>
