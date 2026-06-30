@props([
    'size' => 'md',
    'icon' => null,
    'dismissible' => false,
])

@php
    $sizeClasses = match($size) {
        'lg' => 'px-3 py-1.5 text-sm',             // 14px
        'md' => '',                                // 12px (Hereda el estándar base de .chip)
        'sm' => 'px-2 py-0.5 text-xs',             // 12px compacto
        'xs' => 'px-1.5 py-0.5 text-2xs',          // 10px ultra compacto
        default => '',
    };
@endphp

<span {{ $attributes->except(['wire:click', '@click'])->class(['chip', $sizeClasses]) }}>
    @if($icon)<x-dynamic-component :component="'lucide-' . $icon" class="w-3.5 h-3.5 shrink-0 text-text-muted inline-block" />@endif
    
    <span class="truncate">{{ $slot }}</span>

    @if($dismissible)
        <button
            type="button"
            class="ml-1 -mr-0.5 btn-close-sm hover:text-danger-600"
            @if($attributes->has('wire:click')) wire:click="{{ $attributes->get('wire:click') }}" @endif
            @if($attributes->has('@click')) @click="{{ $attributes->get('@click') }}" @endif
            aria-label="Quitar">
            <x-lucide-x class="w-3.5 h-3.5" aria-hidden="true" />
        </button>
    @endif
</span>
