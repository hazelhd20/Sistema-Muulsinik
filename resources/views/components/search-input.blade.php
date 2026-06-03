@props(['placeholder' => 'Buscar...'])

<div class="relative w-full sm:w-72" x-data="{ focused: false }">
    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
    <input {{ $attributes->merge(['type' => 'search', 'class' => 'input pl-10 pr-10 w-full']) }}
        placeholder="{{ $placeholder }}" @focus="focused = true" @blur="focused = false">
    
    @php
        // Find which wire:model is bound
        $modelBind = $attributes->get('wire:model.live.debounce.50ms') ?? $attributes->get('wire:model.live') ?? $attributes->get('wire:model');
    @endphp

    @if($modelBind)
        <button x-show="$wire.get('{{ $modelBind }}')" 
            x-transition @click="$wire.set('{{ $modelBind }}', '')" type="button"
            class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded hover:bg-surface-hover text-text-muted" style="display:none;">
            <i data-lucide="x" class="w-3.5 h-3.5"></i>
        </button>
    @endif
</div>
