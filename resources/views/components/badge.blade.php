@props([
    'variant' => 'secondary',
    'size' => 'md',
    'dot' => false,
    'icon' => null,
    'dismissible' => false,
    'normalCase' => false,
])

@php
    $sizeClasses = match($size) {
        'lg' => 'px-3 py-1.5 text-xs',             // 12px
        'md' => '',                                // 11px (Hereda el estándar base de .badge)
        'sm' => 'px-2 py-0.5 text-[10px]',         // 10px
        'xs' => 'px-1.5 py-0.5 text-[9px]',        // 9px
        default => '',
    };
    $caseClasses = $normalCase ? 'normal-case font-semibold tracking-normal' : '';
@endphp

<span {{ $attributes->except(['wire:click', '@click'])->class(['badge', "badge-{$variant}", $sizeClasses, $caseClasses]) }}>
    @if($dot)<span class="badge-dot shrink-0"></span>@endif
    @if($icon)<x-dynamic-component :component="'lucide-' . $icon" class="w-3.5 h-3.5 shrink-0 inline-block" />@endif
    
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
