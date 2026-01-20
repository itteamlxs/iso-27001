<?php
/**
 * Input Component
 * @var string $type - input, email, password, text
 * @var string $name
 * @var string $id
 * @var string $label
 * @var string $placeholder
 * @var string $value
 * @var bool $required
 * @var string $error
 * @var string $autocomplete
 * @var array $attributes
 */

$type = $type ?? 'text';
$name = $name ?? '';
$id = $id ?? $name;
$label = $label ?? '';
$placeholder = $placeholder ?? '';
$value = $value ?? '';
$required = $required ?? false;
$error = $error ?? '';
$autocomplete = $autocomplete ?? '';
$attributes = $attributes ?? [];

$inputClasses = "w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 transition " .
    ($error ? "border-red-500 focus:ring-red-500" : "border-gray-300 focus:ring-blue-500 focus:border-blue-500");

$attrString = '';
if ($autocomplete) {
    $attrString .= sprintf(' autocomplete="%s"', htmlspecialchars($autocomplete));
}
foreach ($attributes as $key => $val) {
    $attrString .= sprintf(' %s="%s"', htmlspecialchars($key), htmlspecialchars($val));
}
?>

<div class="mb-4">
    <?php if ($label): ?>
    <label for="<?= htmlspecialchars($id) ?>" class="block text-sm font-medium text-gray-700 mb-2">
        <?= htmlspecialchars($label) ?>
        <?php if ($required): ?>
        <span class="text-red-500">*</span>
        <?php endif; ?>
    </label>
    <?php endif; ?>
    
    <input 
        type="<?= htmlspecialchars($type) ?>"
        id="<?= htmlspecialchars($id) ?>"
        name="<?= htmlspecialchars($name) ?>"
        value="<?= htmlspecialchars($value) ?>"
        placeholder="<?= htmlspecialchars($placeholder) ?>"
        class="<?= $inputClasses ?>"
        <?= $required ? 'required' : '' ?>
        <?= $attrString ?>
    >
    
    <?php if ($error): ?>
    <p class="mt-1 text-sm text-red-600">
        <?= htmlspecialchars($error) ?>
    </p>
    <?php endif; ?>
</div>
