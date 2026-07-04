@props([
    'model' => 'selectedRows'
])

<div 
    x-show="{{ $model }}.length > 0"
    x-transition:enter="transition-premium"
    x-transition:enter-start="opacity-0 translate-y-10"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition-premium"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-10"
    class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-surface-card text-text-primary px-3 sm:px-4 py-2 sm:py-2.5 rounded-2xl shadow-xl border border-border flex items-center gap-2 sm:gap-3 w-auto max-w-[95vw]"
    style="display: none;"
>
    <div class="flex items-center gap-2 sm:gap-3 border-r border-border pr-2 sm:pr-3 shrink-0">
        <x-pulse-indicator color="primary" />
        <span class="text-small font-semibold text-text-primary whitespace-nowrap">
            <span x-text="{{ $model }}.length"></span> <span class="hidden sm:inline">seleccionados</span><span class="sm:hidden">sel.</span>
        </span>
    </div>

    <div class="flex items-center gap-1.5 sm:gap-2">
        {{ $slot }}
    </div>

    <div class="border-l border-border pl-2 shrink-0">
        <button type="button" @click="{{ $model }} = []" class="btn-close hover:text-danger hover:bg-danger/10" title="Deseleccionar todo">
            <x-lucide-x class="w-4 h-4" />
        </button>
    </div>
</div>
