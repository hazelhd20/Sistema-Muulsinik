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
     wire:ignore.self
     x-show="show"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     x-cloak
     class="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto p-4 sm:p-6 lg:p-8"
     role="dialog" aria-modal="true"
     x-trap="show"
     {{ $attributes }}
     @keydown.escape.window="show = false; $wire.set('{{ $show }}', false)"
     x-init="$nextTick(() => {
         const first = $el.querySelector('input:not([type=hidden]):not([type=file]),textarea,select');
         first?.focus();
     })">

    {{-- Backdrop --}}
    <div x-show="show"
         wire:ignore.self
         class="modal-overlay fixed inset-0 bg-black/40 backdrop-blur-[2px]"
         x-transition:enter="transition-premium"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         wire:click="$set('{{ $show }}', false)"
         aria-hidden="true"></div>

    {{-- Panel --}}
    <div x-show="show"
         wire:ignore.self
         class="modal-panel relative bg-surface-card rounded-xl shadow-2xl border border-border
                w-full {{ $maxWidthClass }} my-auto"
         x-transition:enter="transition-premium"
         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-1">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4 px-5 pt-5 pb-4 border-b border-border">
            <div class="min-w-0">
                <h2 class="text-h2 font-bold text-text-primary leading-snug">{{ $title }}</h2>
                @if($subtitle)
                    <p class="text-small font-medium text-text-muted mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button"
                    wire:click="$set('{{ $show }}', false)"
                    class="btn-close -mt-0.5 -mr-0.5"
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
