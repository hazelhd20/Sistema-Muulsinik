@props([
    'model' => 'selectedRows'
])

<div 
    x-show="{{ $model }}.length > 0"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-10"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-10"
    class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900 text-slate-100 px-6 py-3.5 rounded-2xl shadow-2xl border border-slate-800 flex items-center gap-6 min-w-[320px] md:min-w-[450px]"
    style="display: none;"
>
    <div class="flex items-center gap-2 border-r border-slate-800 pr-4 shrink-0">
        <span class="w-2 h-2 rounded-full bg-primary-300 animate-pulse"></span>
        <span class="text-sm font-semibold text-white">
            <span x-text="{{ $model }}.length"></span> seleccionados
        </span>
    </div>

    <div class="flex items-center gap-2 flex-1 overflow-x-auto no-scrollbar">
        {{ $slot }}
    </div>

    <button type="button" @click="{{ $model }} = []" class="text-slate-400 hover:text-slate-200 transition-colors p-1 rounded-lg hover:bg-slate-800 cursor-pointer" title="Deseleccionar todo">
        <i data-lucide="x" class="w-4 h-4"></i>
    </button>
</div>
