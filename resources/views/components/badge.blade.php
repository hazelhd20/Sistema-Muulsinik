@props([
    'variant' => 'secondary',
    'size' => 'sm',
    'dot' => false,
    'icon' => null,
    'dismissible' => false,
])

@php
    $sizeClasses = match($size) {
        'lg' => 'px-3 py-1.5 text-xs',
        'md' => 'px-2.5 py-1 text-[11px]',
        default => '', // usa el padding por defecto de .badge
    };
@endphp

<span {{ $attributes->except(['wire:click', '@click'])->class(['badge', "badge-{$variant}", $sizeClasses]) }}>
    @if($dot)<span class="badge-dot shrink-0"></span>@endif
    @if($icon)<x-dynamic-component :component="'lucide-' . $icon" class="w-3.5 h-3.5 shrink-0 inline-block mr-1" />@endif
    
    <span class="truncate">{{ $slot }}</span>

    @if($dismissible)
        <button
            type="button"
            class="ml-1 -mr-0.5 text-current opacity-60 hover:opacity-100 hover:text-danger focus:outline-none rounded transition-colors"
            @if($attributes->has('wire:click')) wire:click="{{ $attributes->get('wire:click') }}" @endif
            @if($attributes->has('@click')) @click="{{ $attributes->get('@click') }}" @endif
            aria-label="Quitar">
            <x-lucide-x class="w-3.5 h-3.5" aria-hidden="true" />
        </button>
    @endif
</span>
