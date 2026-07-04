@props([
    'value' => '',
    'size' => 'md',
])
@php
    $colors = [
        'bg-primary-50 text-primary-700',
        'bg-purple-50 text-purple-700',
        'bg-indigo-50 text-indigo-700',
        'bg-cyan-50 text-cyan-700',
        'bg-fuchsia-50 text-fuchsia-700',
        'bg-violet-50 text-violet-700',
        'bg-sky-50 text-sky-700',
        'bg-teal-50 text-teal-700',
        'bg-pink-50 text-pink-700',
        'bg-slate-100 text-slate-800 font-semibold',
    ];
    $hash = crc32($value ?? '');
    $colorClass = $colors[$hash % count($colors)];
    $sizeClasses = match($size) {
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
