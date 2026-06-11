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
    class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-surface-card text-text-primary px-4 py-3 rounded-2xl shadow-xl border border-border flex items-center gap-4 min-w-[320px] max-w-[90vw]"
    style="display: none;"
>
    <div class="flex items-center gap-3 border-r border-border pr-4 shrink-0">
        <x-pulse-indicator color="primary" />
        <span class="text-sm font-semibold text-text-primary whitespace-nowrap">
            <span x-text="{{ $model }}.length"></span> seleccionados
        </span>
    </div>

    <div class="flex items-center gap-2 flex-1 overflow-x-auto no-scrollbar">
        {{ $slot }}
    </div>

    <div class="border-l border-border pl-2 shrink-0">
        <button type="button" @click="{{ $model }} = []" class="text-text-muted hover:text-danger transition-colors p-1.5 rounded-lg hover:bg-danger/10 cursor-pointer flex items-center justify-center" title="Deseleccionar todo">
            <x-lucide-x class="w-4 h-4" />
        </button>
    </div>
</div>
