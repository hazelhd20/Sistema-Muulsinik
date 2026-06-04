@props([
    'value' => '',
])
@php
    $colors = [
        'bg-primary-50 text-primary-700 border-primary-200/50',
        'bg-purple-50 text-purple-700 border-purple-200/50',
        'bg-indigo-50 text-indigo-700 border-indigo-200/50',
        'bg-cyan-50 text-cyan-700 border-cyan-200/50',
        'bg-emerald-50 text-emerald-700 border-emerald-200/50',
        'bg-violet-50 text-violet-700 border-violet-200/50',
        'bg-sky-50 text-sky-700 border-sky-200/50',
        'bg-amber-50 text-amber-700 border-amber-200/50',
    ];
    $hash = crc32($value ?? '');
    $colorClass = $colors[$hash % count($colors)];
@endphp

<span {{ $attributes->merge(['class' => "badge {$colorClass}"]) }}>
    {{ $slot->isNotEmpty() ? $slot : $value }}
</span>
