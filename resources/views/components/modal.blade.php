@props([
    'show'     => '',
    'maxWidth' => 'lg',
    'title'    => '',
    'subtitle' => '',
])

@php
    $maxWidthClass = match($maxWidth) {
        'sm'  => 'max-w-sm',
        'md'  => 'max-w-md',
        'lg'  => 'max-w-lg',
        'xl'  => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        default => 'max-w-lg',
    };
@endphp

{{-- Overlay --}}
<div x-data="{ show: @entangle($show) }"
     x-show="show"
     x-cloak
     class="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto p-4 sm:p-6 lg:p-8"
     role="dialog" aria-modal="true"
     x-trap.noscroll="show"
     {{ $attributes }}
     @keydown.escape.window="show = false; $wire.set('{{ $show }}', false)"
     x-init="$nextTick(() => {
         const first = $el.querySelector('input:not([type=hidden]):not([type=file]),textarea,select');
         first?.focus();
     })">

    {{-- Backdrop --}}
    <div class="modal-overlay fixed inset-0 bg-black/40 backdrop-blur-[2px]"
         wire:click="$set('{{ $show }}', false)"
         aria-hidden="true"></div>

    {{-- Panel --}}
    <div class="modal-panel relative bg-surface-card rounded-xl shadow-2xl border border-border
                w-full {{ $maxWidthClass }} my-auto">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4 px-5 pt-5 pb-4 border-b border-border">
            <div class="min-w-0">
                <h2 class="text-h2 font-semibold text-text-primary leading-snug">{{ $title }}</h2>
                @if($subtitle)
                    <p class="text-xs-fluid text-text-muted mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button"
                    wire:click="$set('{{ $show }}', false)"
                    class="shrink-0 p-1.5 -mt-0.5 -mr-0.5 rounded-lg text-text-muted border-transparent focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                           hover:bg-surface-hover hover:text-text-primary transition-colors"
                    aria-label="Cerrar">
                <x-lucide-x class="w-4 h-4" />
            </button>
        </div>

        {{-- Body --}}
        <div class="max-h-[calc(100vh-12rem)] overflow-y-auto">
            {{ $slot }}
        </div>

    </div>
</div>
