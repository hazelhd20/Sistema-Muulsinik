<div class="flex items-center gap-2">
    <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface-card border border-gray-200/60 shadow-sm">
        <i data-lucide="hard-hat" class="w-4 h-4 text-primary-500 shrink-0"></i>
        <select
            wire:model.live="activeProjectId"
            class="text-sm font-medium text-text-primary bg-transparent border-none focus:ring-0 focus:outline-none cursor-pointer pr-6 py-0 appearance-none min-w-[140px] max-w-[200px]"
            id="global-project-selector"
        >
            <option value="">Seleccionar proyecto...</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>
    </div>
</div>
