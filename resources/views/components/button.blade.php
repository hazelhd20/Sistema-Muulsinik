@props([
    'variant' => 'primary', // primary, secondary, success, danger, icon, icon-primary, icon-secondary, icon-danger, icon-success
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'iconRight' => null,
    'target' => null, // para wire:target
])

@php
    $baseClasses = match($variant) {
        'primary' => 'btn-primary relative',
        'secondary' => 'btn-secondary relative',
        'success' => 'btn-success relative',
        'danger' => 'btn-danger relative',
        'icon' => 'btn-icon relative',
        'icon-primary' => 'btn-icon-primary relative',
        'icon-secondary' => 'btn-icon-secondary relative',
        'icon-danger' => 'btn-icon-danger relative',
        'icon-success' => 'btn-icon-success relative',
        default => 'btn-primary relative',
    };

    $isIconButton = str_starts_with($variant, 'icon');
    $iconClass = $isIconButton ? 'w-4 h-4' : 'w-4 h-4 shrink-0';
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $baseClasses]) }}>
        @if($icon)
            <i data-lucide="{{ $icon }}" class="{{ $iconClass }}"></i>
        @endif
        @if(!$isIconButton || $slot->isNotEmpty())
            {{ $slot }}
        @endif
        @if($iconRight)
            <i data-lucide="{{ $iconRight }}" class="{{ $iconClass }}"></i>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $baseClasses]) }}
        @if($target) wire:loading.attr="disabled" wire:target="{{ $target }}" @endif>

        @if($target)
            <span wire:loading.class="opacity-0" wire:target="{{ $target }}" class="inline-flex items-center gap-1.5 transition-opacity">
        @endif

        @if($icon)
            <i data-lucide="{{ $icon }}" class="{{ $iconClass }}"></i>
        @endif

        @if(!$isIconButton || $slot->isNotEmpty())
            {{ $slot }}
        @endif

        @if($iconRight)
            <i data-lucide="{{ $iconRight }}" class="{{ $iconClass }}"></i>
        @endif

        @if($target)
            </span>
            <span wire:loading wire:target="{{ $target }}" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center justify-center">
                <span class="spinner spinner-sm"></span>
            </span>
        @endif
    </button>
@endif
