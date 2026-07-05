@props([
    'value' => '',
    'size' => 'md',
])
@php
    $colorClasses = [
        'badge-cat-blue',
        'badge-cat-purple',
        'badge-cat-indigo',
        'badge-cat-cyan',
        'badge-cat-fuchsia',
        'badge-cat-violet',
        'badge-cat-sky',
        'badge-cat-teal',
        'badge-cat-pink',
        'badge-cat-slate',
    ];
    $hash = crc32($value ?? '');
    $colorClass = $colorClasses[$hash % count($colorClasses)];
    $sizeClasses = match ($size) {
        'lg' => 'px-3 py-1.5 text-xs-fluid',
        'md' => '',
        'sm' => 'px-2 py-0.5 text-2xs',
        'xs' => 'px-1.5 py-0.5 text-3xs',
        default => '',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge {$colorClass} {$sizeClasses}"]) }}>
    {{ $slot->isNotEmpty() ? $slot : $value }}
</span>
