@props(['as' => 'button', 'href' => '#', 'danger' => false, 'success' => false, 'icon' => null, 'iconRight' => null])

@php
    $baseClasses = 'flex items-center gap-2 px-4 py-2.5 text-small font-medium w-full text-left transition-colors duration-150 ';
    if ($danger) {
        $classes = $baseClasses . 'text-danger hover:bg-danger-light hover:text-danger-active';
    } elseif ($success) {
        $classes = $baseClasses . 'text-success hover:bg-success-light hover:text-success-active';
    } else {
        $classes = $baseClasses . 'text-text-secondary hover:bg-surface-hover hover:text-text-primary';
    }
@endphp

@if ($as === 'button')
    <button {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon) <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4 shrink-0" /> @endif
        <span class="flex-1 text-left">{{ $slot }}</span>
        @if($iconRight) <x-dynamic-component :component="'lucide-' . $iconRight" class="w-4 h-4 shrink-0" /> @endif
    </button>
@elseif ($as === 'a')
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon) <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4 shrink-0" /> @endif
        <span class="flex-1">{{ $slot }}</span>
        @if($iconRight) <x-dynamic-component :component="'lucide-' . $iconRight" class="w-4 h-4 shrink-0" /> @endif
    </a>
@endif