<div class="relative">
    <i data-lucide="hard-hat" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-primary-500 pointer-events-none"></i>
    <select
        wire:model.live="activeProjectId"
        class="input pl-10 pr-8 appearance-none cursor-pointer min-w-[180px] max-w-[250px]"
        id="global-project-selector"
    >
        <option value="">Seleccionar proyecto...</option>
        @foreach($projects as $project)
            <option value="{{ $project->id }}">{{ $project->name }}</option>
        @endforeach
    </select>
    <i data-lucide="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted pointer-events-none"></i>
</div>
