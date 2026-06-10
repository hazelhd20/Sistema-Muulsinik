@props(['placeholder' => 'Buscar...'])

@php
    // Detectar qué wire:model se está utilizando
    $model = $attributes->whereStartsWith('wire:model')->first();
@endphp

<div class="relative w-full sm:w-72"
     x-data="{
        query: '',
        init() {
            // Inicializar con el valor actual del input (por si viene pre-cargado)
            this.query = this.$refs.input.value;
        },
        clear() {
            this.query = '';
            this.$refs.input.value = '';
            // Disparar evento para que Livewire actualice el estado
            this.$refs.input.dispatchEvent(new Event('input'));
        }
     }">

    {{-- 
        NOTA ARQUITECTÓNICA: Lupa SVG nativa (No parpadea)
        Se utiliza un SVG inline en lugar de `<i data-lucide="search">` o un componente `<x-icon>` 
        porque este input suele estar en la parte superior del DOM (header/filtros) y es altamente visible. 
        Si dependemos de la inicialización asíncrona de `lucide.createIcons()` en el cliente, 
        el icono puede causar un parpadeo visual (FOUC) molesto.
    --}}
    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"
         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
    </svg>

    {{-- Input --}}
    <input
        x-ref="input"
        @input="query = $el.value"
        autocomplete="off"
        autocorrect="off"
        autocapitalize="none"
        spellcheck="false"
        {{ $attributes->merge(['type' => 'search', 'class' => 'input pl-10 pr-10 w-full']) }}
        placeholder="{{ $placeholder }}"
    >

    {{-- Botón de Borrado (X) nativo (No parpadea, instantáneo) --}}
    <button
        x-show="query.length > 0"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        @click="clear()"
        type="button"
        class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded-md hover:bg-surface-hover text-text-muted hover:text-text-primary transition-colors"
        style="display:none;"
    >
        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
    </button>
</div>
