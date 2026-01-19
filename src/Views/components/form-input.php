<?php
$type = $type ?? 'text';
$name = $name ?? '';
$label = $label ?? '';
$placeholder = $placeholder ?? '';
$value = $value ?? old($name);
$required = $required ?? false;
$error = $error ?? null;
$autocomplete = $autocomplete ?? '';
$id = $id ?? $name;
$disabled = $disabled ?? false;
?>

<div class="space-y-2">
    <?php if ($label): ?>
        <label for="<?= e($id) ?>" class="block text-sm font-medium text-gray-700">
            <?= e($label) ?>
            <?php if ($required): ?>
                <span class="text-red-500">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>
    
    <input 
        type="<?= e($type) ?>" 
        id="<?= e($id) ?>" 
        name="<?= e($name) ?>" 
        value="<?= e($value) ?>"
        <?= $required ? 'required' : '' ?>
        <?= $disabled ? 'disabled' : '' ?>
        <?= $autocomplete ? 'autocomplete="' . e($autocomplete) . '"' : '' ?>
        <?= $placeholder ? 'placeholder="' . e($placeholder) . '"' : '' ?>
        class="w-full px-4 py-3 border <?= $error ? 'border-red-500' : 'border-gray-300' ?> rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition <?= $disabled ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
    >
    
    <?php if ($error): ?>
        <p class="text-sm text-red-600"><?= e($error) ?></p>
    <?php endif; ?>
</div>
