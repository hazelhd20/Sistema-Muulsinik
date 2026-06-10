@props([
    'activeCount' => 0,
    'align'       => 'right',
    'columns'     => 1,
])

@php
    /*
     * Clases pre-calculadas en PHP para no mezclar expresiones PHP
     * dentro de atributos Alpine (:class usa sólo JS).
     */
    $panelWidth = $columns >= 2 ? 'w-[34rem]' : 'w-80 sm:w-[26rem]';
    $classDown  = $align === 'right' ? 'top-full mt-2 origin-top-right'    : 'top-full mt-2 origin-top-left';
    $classUp    = $align === 'right' ? 'bottom-full mb-2 origin-bottom-right' : 'bottom-full mb-2 origin-bottom-left';
    $gridClass  = $columns >= 2 ? 'grid grid-cols-2 gap-x-5 gap-y-4' : 'space-y-4';
@endphp

<div
    x-data="{
        open: false,
        dropUp: false,
        contentMaxH: '50vh',

        toggle() {
            if (this.open) { this.open = false; return; }

            this.open = true;
            this.$dispatch('filters-opened');

            this.$nextTick(() => {
                const btn = this.$refs.trigger;
                if (!btn) return;

                const rect = btn.getBoundingClientRect();

                /*
                 * CHROME ≈ panel-header (48px) + panel-footer (68px)
                 *         + content padding top/bottom (40px) + margen (16px)
                 * Usamos 172 como estimado conservador para no cortar contenido.
                 */
                const CHROME     = 120;
                const spaceBelow = window.innerHeight - rect.bottom - 16;
                const spaceAbove = rect.top - 16;

                if (spaceBelow < 360 && spaceAbove > spaceBelow) {
                    /* No hay suficiente espacio abajo Y hay más arriba → abrir hacia arriba */
                    this.dropUp      = true;
                    this.contentMaxH = Math.max(spaceAbove - CHROME, 280) + 'px';
                } else {
                    /* Caso normal: abrir hacia abajo */
                    this.dropUp      = false;
                    this.contentMaxH = Math.max(spaceBelow - CHROME, 280) + 'px';
                }
            });
        }
    }"
    @click.outside="open = false"
    @close.stop="open = false"
    class="relative inline-block text-left z-30"
>
    {{-- ── Trigger ── --}}
    <div x-ref="trigger" @click="toggle()">
        <x-button type="button" variant="secondary" icon="filter" class="shrink-0"
            x-bind:class="{ 'bg-primary-50 border-primary-200 text-primary-700': {{ $activeCount }} > 0 || open }">
            Filtros
            @if($activeCount > 0)
                <span class="count-badge ml-1.5">{{ $activeCount }}</span>
            @endif
        </x-button>
    </div>

    {{-- ── Panel ── --}}
    <div
        x-ref="panel"
        x-show="open"
        wire:ignore
        x-transition:enter="transition-premium"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition-premium"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        :class="dropUp ? '{{ $classUp }}' : '{{ $classDown }}'"
        class="absolute {{ $align === 'right' ? 'right-0' : 'left-0' }} {{ $panelWidth }}
               rounded-xl bg-surface-card shadow-xl border border-border overflow-hidden"
        style="display: none;"
        @keydown.escape.window="open = false"
    >
        {{-- Cabecera --}}
        <div class="px-5 py-3 border-b border-border flex justify-between items-center bg-surface-main/30 shrink-0">
            <h3 class="text-sm font-semibold text-text-primary flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4 text-text-muted"></i>
                Filtros
            </h3>
            <button type="button" @click="open = false"
                class="text-text-muted hover:text-text-primary transition-colors p-1 rounded-md hover:bg-surface-hover">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        {{-- Contenido (altura dinámica según espacio disponible) --}}
        <div class="p-5 overflow-y-auto {{ $gridClass }}"
             :style="'max-height:' + contentMaxH">
            {{ $slot }}
        </div>

        {{-- Pie (opcional) --}}
        @if(isset($footer))
            <div class="px-5 py-4 border-t border-border bg-surface-main/50 flex justify-between items-center shrink-0">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
