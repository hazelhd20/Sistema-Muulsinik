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
    $panelWidth = $columns >= 2 ? 'w-[calc(100vw-2.5rem)] sm:w-[34rem]' : 'w-[calc(100vw-2.5rem)] sm:w-[26rem]';
    $classDown  = $align === 'right' ? 'top-full mt-2 origin-top-right'    : 'top-full mt-2 origin-top-left';
    $classUp    = $align === 'right' ? 'bottom-full mb-2 origin-bottom-right' : 'bottom-full mb-2 origin-bottom-left';
    $gridClass  = $columns >= 2 ? 'grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-x-5 sm:gap-y-4' : 'space-y-4';
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
    <div x-ref="trigger" @click="toggle()" :aria-expanded="open.toString()">
        <x-button type="button" variant="soft" icon="filter" class="shrink-0 !bg-surface-card md:!bg-secondary-light shadow-sm md:shadow-none"
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
        <div class="dropdown-header">
            <h3 class="dropdown-header-title">
                <x-lucide-filter class="w-4 h-4 text-text-muted" />
                Filtros
            </h3>
            <button type="button" @click="open = false" class="btn-close -mr-1">
                <x-lucide-x class="w-4 h-4" />
            </button>
        </div>

        {{-- Contenido (altura dinámica según espacio disponible) --}}
        <div class="p-5 overflow-y-auto {{ $gridClass }}"
             :style="'max-height:' + contentMaxH">
            {{ $slot }}
        </div>

        {{-- Pie (opcional) --}}
        @if(isset($footer))
            <div class="dropdown-footer !justify-between">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
