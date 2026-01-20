<?php
/**
 * Stats Card Component
 * @var string $title
 * @var int $value
 * @var string $icon - SVG path
 * @var string $color - blue, yellow, green, purple
 */

$title = $title ?? 'Métrica';
$value = $value ?? 0;
$icon = $icon ?? '';
$color = $color ?? 'blue';

$colorClasses = [
    'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
    'yellow' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
    'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
    'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
];

$classes = $colorClasses[$color] ?? $colorClasses['blue'];
?>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <div class="w-12 h-12 <?= $classes['bg'] ?> rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 <?= $classes['text'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <?= $icon ?>
                </svg>
            </div>
        </div>
        <div class="ml-4">
            <p class="text-sm font-medium text-gray-600"><?= htmlspecialchars($title) ?></p>
            <p class="text-2xl font-semibold text-gray-900"><?= htmlspecialchars($value) ?></p>
        </div>
    </div>
</div>
