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
                <p class="text-sm text-gray-600">Dominio</p>
                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($control['dominio_nombre']) ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Aplicable</p>
                <?php if ($control['aplicable']): ?>
                    <p class="text-sm font-medium text-green-600">Sí</p>
                <?php else: ?>
                    <p class="text-sm font-medium text-red-600">No</p>
                <?php endif; ?>
            </div>
            <?php if ($control['fecha_evaluacion']): ?>
            <div>
                <p class="text-sm text-gray-600">Última Evaluación</p>
                <p class="text-sm font-medium text-gray-900"><?= date('d/m/Y H:i', strtotime($control['fecha_evaluacion'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Formulario de Evaluación -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Evaluación del Control</h2>

        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="flex-1">
                        <?php foreach ($errors as $field => $fieldErrors): ?>
                            <?php foreach ($fieldErrors as $error): ?>
                                <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

	<form method="POST" action="/controles/<?= $control['id'] ?>/evaluar" class="space-y-6">
            <?= csrf_field() ?>

            <!-- Aplicabilidad -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    ¿Es aplicable este control a su organización? <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-4">
                    <label class="inline-flex items-center cursor-pointer">
                        <input 
                            type="radio" 
                            name="aplicable" 
                            value="1" 
                            <?= ($control['aplicable'] == 1) ? 'checked' : '' ?> 
                            class="w-4 h-4 text-blue-600 focus:ring-blue-500" 
                            required
                        >
                        <span class="ml-2 text-sm text-gray-700">Sí, es aplicable</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer">
                        <input 
                            type="radio" 
                            name="aplicable" 
                            value="0" 
                            <?= ($control['aplicable'] == 0) ? 'checked' : '' ?> 
                            class="w-4 h-4 text-blue-600 focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">No es aplicable</span>
                    </label>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Indique si este control es relevante para los riesgos de seguridad de su organización
                </p>
            </div>

            <!-- Justificación (solo si NO es aplicable) -->
            <div id="justificacionSection" class="hidden">
                <label for="justificacion_no_aplicable" class="block text-sm font-medium text-gray-700 mb-2">
                    Justificación de No Aplicabilidad <span class="text-red-500">*</span>
                </label>
                <textarea 
                    name="justificacion_no_aplicable" 
                    id="justificacion_no_aplicable" 
                    rows="4" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Explique por qué este control no es aplicable a su organización (mínimo 20 caracteres)..."
                ><?= htmlspecialchars($control['justificacion_no_aplicable'] ?? '') ?></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Debe proporcionar una justificación clara y detallada (mínimo 20 caracteres)
                </p>
            </div>

            <!-- Estado de Implementación (solo si ES aplicable) -->
            <div id="estadoSection">
                <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                    Estado de Implementación <span class="text-red-500">*</span>
                </label>
                <select 
                    name="estado" 
                    id="estado" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                    required
                >
                    <option value="">Seleccione un estado...</option>
                    <option value="no_implementado" <?= ($control['estado'] == 'no_implementado') ? 'selected' : '' ?>>
                        No Implementado - Aún no se ha trabajado en este control
                    </option>
                    <option value="parcial" <?= ($control['estado'] == 'parcial') ? 'selected' : '' ?>>
                        Parcialmente Implementado - Control en proceso de implementación
                    </option>
                    <option value="implementado" <?= ($control['estado'] == 'implementado') ? 'selected' : '' ?>>
                        Implementado - Control completamente funcional
                    </option>
                </select>
            </div>

            <!-- Notas Adicionales -->
            <div>
                <label for="notas" class="block text-sm font-medium text-gray-700 mb-2">
                    Notas Adicionales
                </label>
                <textarea 
                    name="notas" 
                    id="notas" 
                    rows="4" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Agregue cualquier información adicional relevante sobre la evaluación de este control..."
                ><?= htmlspecialchars($control['notas'] ?? '') ?></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Opcional: Detalles sobre la implementación, observaciones, o información contextual
                </p>
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center gap-3 pt-4 border-t border-gray-200">
                <button 
                    type="submit" 
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Guardar Evaluación
                </button>
                <a 
                    href="/controles" 
                    class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors"
                >
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="aplicable"]');
    const justSection = document.getElementById('justificacionSection');
    const estSection = document.getElementById('estadoSection');
    const justTextarea = document.getElementById('justificacion_no_aplicable');
    const estadoSelect = document.getElementById('estado');
    
    function toggleSections() {
        const aplicable = document.querySelector('input[name="aplicable"]:checked')?.value;
        
        if (aplicable === '0') {
            // No aplicable: mostrar justificación, ocultar estado
            justSection.classList.remove('hidden');
            estSection.classList.add('hidden');
            justTextarea.required = true;
            estadoSelect.required = false;
            estadoSelect.value = 'no_implementado'; // Forzar estado
        } else if (aplicable === '1') {
            // Aplicable: ocultar justificación, mostrar estado
            justSection.classList.add('hidden');
            estSection.classList.remove('hidden');
            justTextarea.required = false;
            justTextarea.value = ''; // Limpiar justificación
            estadoSelect.required = true;
        }
    }
    
    radios.forEach(radio => radio.addEventListener('change', toggleSections));
    
    // Ejecutar al cargar para reflejar estado actual
    toggleSections();
});
</script>
