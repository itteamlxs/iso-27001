<?php
/**
 * Button Component
 * @var string $type - button, submit, reset
 * @var string $text - Texto del botón
 * @var string $variant - primary, secondary, danger, success
 * @var bool $fullWidth
 * @var string $id
 * @var array $attributes
 */

$type = $type ?? 'button';
$text = $text ?? 'Click';
$variant = $variant ?? 'primary';
$fullWidth = $fullWidth ?? false;
$id = $id ?? '';
$attributes = $attributes ?? [];

$variantClasses = [
    'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    'secondary' => 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
];

$classes = ($variantClasses[$variant] ?? $variantClasses['primary']) . ' px-4 py-3 text-base';
$classes .= $fullWidth ? ' w-full' : '';
$classes .= ' rounded-lg font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2';

$attrString = '';
foreach ($attributes as $key => $val) {
    $attrString .= sprintf(' %s="%s"', htmlspecialchars($key), htmlspecialchars($val));
}
?>

<button 
    type="<?= htmlspecialchars($type) ?>"
    <?= $id ? 'id="' . htmlspecialchars($id) . '"' : '' ?>
    class="<?= $classes ?>"
    <?= $attrString ?>
>
    <?= htmlspecialchars($text) ?>
</button>
