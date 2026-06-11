@props([
    'color' => 'primary', // primary, danger, success, warning
])

@php
    $baseClass = "relative inline-flex rounded-full h-2.5 w-2.5 ";
    $pingClass = "animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 ";
    
    $colors = [
        'primary' => ['bg' => 'bg-primary-500', 'ping' => 'bg-primary-400'],
        'danger' => ['bg' => 'bg-danger-500', 'ping' => 'bg-danger-400'],
        'success' => ['bg' => 'bg-success-500', 'ping' => 'bg-success-400'],
        'warning' => ['bg' => 'bg-warning-500', 'ping' => 'bg-warning-400'],
    ];

    $selectedColor = $colors[$color] ?? $colors['primary'];
@endphp

<span {{ $attributes->merge(['class' => 'relative flex h-2.5 w-2.5']) }}>
    <span class="{{ $pingClass }} {{ $selectedColor['ping'] }}"></span>
    <span class="{{ $baseClass }} {{ $selectedColor['bg'] }}"></span>
</span>
