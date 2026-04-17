<div class="relative">
    <x-custom-select
        wire:model.live="activeProjectId"
        :options="$projects->pluck('name', 'id')->toArray()"
        placeholder="Seleccionar proyecto..."
        class="min-w-[200px] max-w-[250px] text-sm"
    >
        <i data-lucide="hard-hat" class="w-4 h-4 text-primary-500"></i>
    </x-custom-select>
</div>
