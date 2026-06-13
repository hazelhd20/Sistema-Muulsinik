@props([
    'value' => '',
])
@php
    $colors = [
        'bg-primary-50 text-primary-700 border-primary-200/50',
        'bg-purple-50 text-purple-700 border-purple-200/50',
        'bg-indigo-50 text-indigo-700 border-indigo-200/50',
        'bg-cyan-50 text-cyan-700 border-cyan-200/50',
        'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200/50',
        'bg-violet-50 text-violet-700 border-violet-200/50',
        'bg-sky-50 text-sky-700 border-sky-200/50',
        'bg-teal-50 text-teal-700 border-teal-200/50',
        'bg-pink-50 text-pink-700 border-pink-200/50',
        'bg-slate-50 text-slate-700 border-slate-200/50',
    ];
    $hash = crc32($value ?? '');
    $colorClass = $colors[$hash % count($colors)];
@endphp

<span {{ $attributes->merge(['class' => "badge {$colorClass}"]) }}>
    {{ $slot->isNotEmpty() ? $slot : $value }}
</span>
