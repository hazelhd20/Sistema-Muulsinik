@props([
    'show'     => '',
    'maxWidth' => 'md',
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
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        'full' => 'max-w-full',
        default => 'max-w-md',
    };
@endphp

{{-- Overlay --}}
<div x-data="{ show: @entangle($show), touchStartX: 0, touchStartY: 0 }"
     @touchstart.passive="touchStartX = $event.touches[0].clientX; touchStartY = $event.touches[0].clientY"
     @touchend.passive="
         if (show) {
             let deltaX = $event.changedTouches[0].clientX - touchStartX;
             let deltaY = Math.abs($event.changedTouches[0].clientY - touchStartY);
             if (deltaX > 50 && deltaX > deltaY * 1.5) {
                 $wire.set('{{ $show }}', false);
             }
         }
     "
     x-show="show"
     x-cloak
     class="fixed inset-0 z-[60] overflow-hidden"
     role="dialog" aria-modal="true"
     x-trap.noscroll="show"
     @keydown.escape.window="$wire.set('{{ $show }}', false)"
     x-init="$watch('show', value => {
         if (value) {
             $nextTick(() => {
                 const first = $el.querySelector('input:not([type=hidden]):not([type=file]),textarea,select,button:not([disabled])');
                 first?.focus();
             });
         }
     })">

    {{-- Backdrop --}}
    <div x-show="show"
         x-transition:enter="transition-premium"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute inset-0 bg-black/40 backdrop-blur-[2px] transition-opacity"
         wire:click="$set('{{ $show }}', false)"
         aria-hidden="true"></div>

    {{-- Panel --}}
    <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
        <div x-show="show"
             x-transition:enter="transition-premium"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="w-full max-w-full {{ $maxWidthClass }} overflow-x-hidden">
            
            <div class="flex h-full flex-col bg-surface-card shadow-2xl border-l border-border
                        sm:rounded-l-2xl overflow-hidden">
                
                {{-- Header --}}
                <div class="flex items-start justify-between gap-4 px-6 py-5 border-b border-border bg-surface-card shrink-0">
                    <div class="min-w-0">
                        @if($title instanceof \Illuminate\View\ComponentSlot)
                            {{ $title }}
                        @else
                            <h2 class="text-h2 font-bold text-text-primary leading-snug">{{ $title }}</h2>
                            @if($subtitle)
                                <p class="text-small font-medium text-text-secondary mt-0.5">{{ $subtitle }}</p>
                            @endif
                        @endif
                    </div>
                    <div class="ml-3 flex h-7 items-center">
                        <button type="button"
                                wire:click="$set('{{ $show }}', false)"
                                class="btn-close -mr-1.5"
                                aria-label="Cerrar">
                            <span class="absolute -inset-2.5"></span>
                            <x-lucide-x class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="relative flex-1 overflow-y-auto px-6 py-6 sm:px-6">
                    {{ $slot }}
                </div>

                {{-- Footer sticky (opcional) --}}
                @if(isset($footer))
                    <div class="shrink-0 px-6 py-4 border-t border-border/80 bg-surface-card shadow-[0_-6px_16px_rgba(0,0,0,0.05)] z-10 relative">
                        {{ $footer }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
