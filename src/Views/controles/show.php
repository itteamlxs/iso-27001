<div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="/controles" class="text-gray-700 hover:text-blue-600 inline-flex items-center text-sm">Controles</a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="ml-1 text-sm text-gray-500"><?= htmlspecialchars($control['codigo']) ?></span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-sm font-mono font-semibold text-blue-600"><?= htmlspecialchars($control['codigo']) ?></span>
                    <?php
                    $estados = [
                        'implementado' => ['text' => 'Implementado', 'class' => 'bg-green-100 text-green-800'],
                        'parcial' => ['text' => 'Parcial', 'class' => 'bg-yellow-100 text-yellow-800'],
                        'no_implementado' => ['text' => 'No Implementado', 'class' => 'bg-red-100 text-red-800']
                    ];
                    $estadoInfo = $estados[$control['estado']] ?? ['text' => $control['estado'], 'class' => 'bg-gray-100 text-gray-800'];
                    ?>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $estadoInfo['class'] ?>">
                        <?= $estadoInfo['text'] ?>
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($control['nombre']) ?></h1>
                <p class="text-gray-700"><?= htmlspecialchars($control['descripcion']) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-200">
            <div>
                <p class="text-sm text-gray-600">Aplicable</p>
                <?php if ($control['aplicable']): ?>
                    <p class="text-sm font-medium text-green-600">Sí</p>
                <?php else: ?>
                    <p class="text-sm font-medium text-red-600">No</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Formulario -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Evaluación del Control</h2>

        <form method="POST" action="/controles/<?= $control['id'] ?>/evaluar" class="space-y-6">
            <?= csrf_field() ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">¿Aplicable? <span class="text-red-500">*</span></label>
                <div class="flex gap-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="aplicable" value="1" <?= ($control['aplicable'] == 1) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600" required>
                        <span class="ml-2 text-sm">Sí</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="aplicable" value="0" <?= ($control['aplicable'] == 0) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600">
                        <span class="ml-2 text-sm">No</span>
                    </label>
                </div>
            </div>

            <div id="justificacionSection" class="hidden">
                <label for="justificacion_no_aplicable" class="block text-sm font-medium text-gray-700 mb-2">Justificación <span class="text-red-500">*</span></label>
                <textarea name="justificacion_no_aplicable" id="justificacion_no_aplicable" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($control['justificacion_no_aplicable'] ?? '') ?></textarea>
            </div>

            <div id="estadoSection">
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">Estado <span class="text-red-500">*</span></label>
                <select name="estado" id="estado" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    <option value="">Seleccionar...</option>
                    <option value="no_implementado" <?= ($control['estado'] == 'no_implementado') ? 'selected' : '' ?>>No Implementado</option>
                    <option value="parcial" <?= ($control['estado'] == 'parcial') ? 'selected' : '' ?>>Parcial</option>
                    <option value="implementado" <?= ($control['estado'] == 'implementado') ? 'selected' : '' ?>>Implementado</option>
                </select>
            </div>

            <div>
                <label for="notas" class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
                <textarea name="notas" id="notas" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg"><?= htmlspecialchars($control['notas'] ?? '') ?></textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg">Guardar</button>
                <a href="/controles" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="aplicable"]');
    const just = document.getElementById('justificacionSection');
    const est = document.getElementById('estadoSection');
    
    function toggle() {
        const val = document.querySelector('input[name="aplicable"]:checked')?.value;
        if (val === '0') {
            just.classList.remove('hidden');
            est.classList.add('hidden');
        } else {
            just.classList.add('hidden');
            est.classList.remove('hidden');
        }
    }
    
    radios.forEach(r => r.addEventListener('change', toggle));
    toggle();
});
</script>
