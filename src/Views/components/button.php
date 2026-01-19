<?php
$type = $type ?? 'button';
$text = $text ?? 'Button';
$variant = $variant ?? 'primary';
$size = $size ?? 'md';
$fullWidth = $fullWidth ?? false;
$disabled = $disabled ?? false;
$id = $id ?? '';
$onclick = $onclick ?? '';

$variants = [
    'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    'secondary' => 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
    'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'outline' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-blue-500',
];

$sizes = [
    'sm' => 'px-3 py-2 text-sm',
    'md' => 'px-4 py-3 text-base',
    'lg' => 'px-6 py-4 text-lg',
];

$variantClass = $variants[$variant] ?? $variants['primary'];
$sizeClass = $sizes[$size] ?? $sizes['md'];
$widthClass = $fullWidth ? 'w-full' : '';
?>

<button 
    type="<?= e($type) ?>"
    <?= $id ? 'id="' . e($id) . '"' : '' ?>
    <?= $onclick ? 'onclick="' . e($onclick) . '"' : '' ?>
    <?= $disabled ? 'disabled' : '' ?>
    class="<?= $variantClass ?> <?= $sizeClass ?> <?= $widthClass ?> rounded-lg font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2 <?= $disabled ? 'opacity-50 cursor-not-allowed' : '' ?>"
>
    <?= e($text) ?>
</button>
