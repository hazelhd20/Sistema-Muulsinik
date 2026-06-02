@props([
    'value' => '',
])
@php
    $colors = [
        'bg-blue-50 text-blue-700 border-blue-200',
        'bg-purple-50 text-purple-700 border-purple-200',
        'bg-indigo-50 text-indigo-700 border-indigo-200',
        'bg-cyan-50 text-cyan-700 border-cyan-200',
        'bg-pink-50 text-pink-700 border-pink-200',
        'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
        'bg-violet-50 text-violet-700 border-violet-200',
        'bg-sky-50 text-sky-700 border-sky-200',
        'bg-slate-50 text-slate-700 border-slate-200',
        'bg-stone-50 text-stone-700 border-stone-200',
    ];
    $hash = crc32($value ?? '');
    $colorClass = $colors[$hash % count($colors)];
@endphp

   
<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded-full text-xs-fluid font-medium border {$colorClass}"]) }}>
    {{ $slot->isNotEmpty() ? $slot : $value }}
</span>
