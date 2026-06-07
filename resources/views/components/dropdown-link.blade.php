@props(['as' => 'button', 'href' => '#', 'danger' => false])

@php
$baseClasses = 'flex items-center gap-3 px-4 py-2.5 text-sm font-medium w-full text-left transition-colors duration-150 ';
if ($danger) {
    $classes = $baseClasses . 'text-danger hover:bg-danger-50';
} else {
    $classes = $baseClasses . 'text-text-secondary hover:bg-surface-hover hover:text-text-primary';
}
@endphp

@if ($as === 'button')
    <button {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@elseif ($as === 'a')
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@endif
