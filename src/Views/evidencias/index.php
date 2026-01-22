<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Evidencias de Cumplimiento</h1>
            <p class="mt-1 text-sm text-gray-600">Gestiona los documentos y archivos que respaldan la implementación de controles</p>
        </div>
        <a 
            href="/evidencias/upload" 
            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
            </svg>
            Subir Evidencia
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Evidencias</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['total'] ?? 0 ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Aprobadas</p>
                    <p class="text-2xl font-bold text-green-600 mt-1"><?= $stats['aprobadas'] ?? 0 ?></p>
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
                    <p class="text-sm text-gray-600">Pendientes</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1"><?= $stats['pendientes'] ?? 0 ?></p>
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
                    <p class="text-sm text-gray-600">Tamaño Total</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['tamano_total'] ?? '0 MB' ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <form method="GET" action="/evidencias" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input 
                    type="text" 
                    name="search" 
                    id="search"
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                    placeholder="Nombre o descripción..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>

            <div>
                <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select 
                    name="tipo" 
                    id="tipo"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Todos</option>
                    <option value="documento" <?= ($_GET['tipo'] ?? '') == 'documento' ? 'selected' : '' ?>>Documento</option>
                    <option value="imagen" <?= ($_GET['tipo'] ?? '') == 'imagen' ? 'selected' : '' ?>>Imagen</option>
                    <option value="captura" <?= ($_GET['tipo'] ?? '') == 'captura' ? 'selected' : '' ?>>Captura de Pantalla</option>
                    <option value="otro" <?= ($_GET['tipo'] ?? '') == 'otro' ? 'selected' : '' ?>>Otro</option>
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
                    <option value="pendiente" <?= ($_GET['estado'] ?? '') == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="aprobado" <?= ($_GET['estado'] ?? '') == 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                    <option value="rechazado" <?= ($_GET['estado'] ?? '') == 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
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

    <!-- Grid de Evidencias -->
    <?php if (empty($evidencias)): ?>
        <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay evidencias</h3>
            <p class="mt-1 text-sm text-gray-500">Comienza subiendo tu primera evidencia de cumplimiento</p>
            <div class="mt-6">
                <a href="/evidencias/upload" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Subir Evidencia
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($evidencias as $evidencia): ?>
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
                    <!-- Thumbnail -->
                    <div class="h-48 bg-gray-100 flex items-center justify-center">
                        <?php
                        $extension = pathinfo($evidencia['nombre_archivo'], PATHINFO_EXTENSION);
                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        ?>
                        
                        <?php if (in_array(strtolower($extension), $imageExtensions)): ?>
                            <img 
                                src="/storage/evidencias/<?= htmlspecialchars($evidencia['ruta_archivo']) ?>" 
                                alt="<?= htmlspecialchars($evidencia['nombre']) ?>"
                                class="w-full h-full object-cover"
                            >
                        <?php else: ?>
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <p class="mt-2 text-xs text-gray-500 uppercase"><?= htmlspecialchars($extension) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="text-sm font-semibold text-gray-900 line-clamp-1">
                                <?= htmlspecialchars($evidencia['nombre']) ?>
                            </h3>
                            <?php
                            $estados = [
                                'pendiente' => ['class' => 'bg-yellow-100 text-yellow-800'],
                                'aprobado' => ['class' => 'bg-green-100 text-green-800'],
                                'rechazado' => ['class' => 'bg-red-100 text-red-800']
                            ];
                            $estadoInfo = $estados[$evidencia['estado']] ?? ['class' => 'bg-gray-100 text-gray-800'];
                            ?>
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $estadoInfo['class'] ?>">
                                <?= ucfirst($evidencia['estado']) ?>
                            </span>
                        </div>

                        <?php if (!empty($evidencia['descripcion'])): ?>
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?= htmlspecialchars($evidencia['descripcion']) ?></p>
                        <?php endif; ?>

                        <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                            <span><?= htmlspecialchars($evidencia['control_codigo'] ?? 'Sin control') ?></span>
                            <span><?= number_format($evidencia['tamano'] / 1024, 2) ?> KB</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <a 
                                href="/evidencias/<?= $evidencia['id'] ?>" 
                                class="flex-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded text-center transition-colors"
                            >
                                Ver
                            </a>
                            <a 
                                href="/storage/evidencias/<?= htmlspecialchars($evidencia['ruta_archivo']) ?>" 
                                download
                                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded transition-colors"
                                title="Descargar"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const tipoSelect = document.getElementById('tipo');
    const estadoSelect = document.getElementById('estado');

    [tipoSelect, estadoSelect].forEach(el => {
        el?.addEventListener('change', function() {
            this.form.submit();
        });
    });
});
</script>
