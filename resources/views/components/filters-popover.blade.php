@props([
    'activeCount' => 0,
    'align' => 'right'
])

<div x-data="{ open: false }" x-init="$watch('open', value => { if (value) $dispatch('filters-opened'); })" @click.outside="open = false" @close.stop="open = false" class="relative inline-block text-left z-30">
    <div @click="open = !open">
        <x-button type="button" variant="secondary" icon="sliders-horizontal" class="shrink-0"
            x-bind:class="{ 'bg-primary-50 border-primary-200 text-primary-700': {{ $activeCount }} > 0 || open }">
            Filtros
            @if($activeCount > 0)
                <span class="ml-1.5 px-1.5 py-0.5 bg-primary-600 text-white text-[10px] font-bold rounded-full">{{ $activeCount }}</span>
            @endif
        </x-button>
    </div>

    <div x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="absolute {{ $align === 'right' ? 'right-0' : 'left-0' }} mt-2 w-80 sm:w-[26rem] rounded-xl bg-surface-card shadow-xl border border-border focus:outline-none overflow-hidden"
        style="display: none;"
        @keydown.escape.window="open = false">
        
        <div class="px-5 py-3 border-b border-border flex justify-between items-center bg-surface-main/30">
            <h3 class="text-sm font-semibold text-text-primary flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4 text-text-muted"></i>
                Filtros
            </h3>
            <button type="button" @click="open = false" class="text-text-muted hover:text-text-primary transition-colors p-1 rounded-md hover:bg-surface-hover">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        
        <div class="p-5 max-h-[60vh] overflow-y-auto space-y-4">
            {{ $slot }}
        </div>
        
        @if(isset($footer))
        <div class="px-5 py-4 border-t border-border bg-surface-main/50 flex justify-between items-center">
            {{ $footer }}
        </div>
        @endif
    </div>
</div>
