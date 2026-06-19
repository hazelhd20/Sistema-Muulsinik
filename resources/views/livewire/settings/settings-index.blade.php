<x-modal show="isOpen" title="Configuración del Sistema" maxWidth="5xl" @open-settings.window="show = true">
    <div x-data="{ tab: @entangle('activeTab') }" class="flex flex-col md:flex-row h-[80vh] md:h-[580px] max-h-none md:max-h-[70vh] overflow-hidden">
        {{-- Sidebar Interno --}}
        <div class="w-full md:w-56 border-b md:border-b-0 md:border-r border-border bg-surface-hover/20 p-4 shrink-0 flex flex-col justify-between">
            <nav class="space-y-1">
                <button type="button" @click="tab = 'empresa'" 
                        :class="tab === 'empresa' ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-text-secondary hover:bg-surface-hover'"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-md text-body transition-all duration-150 text-left cursor-pointer">
                    <x-lucide-building-2 class="w-4 h-4 shrink-0" />
                    <span>Empresa</span>
                </button>
                <button type="button" @click="tab = 'documentos'" 
                        :class="tab === 'documentos' ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-text-secondary hover:bg-surface-hover'"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-md text-body transition-all duration-150 text-left cursor-pointer">
                    <x-lucide-file-text class="w-4 h-4 shrink-0" />
                    <span>Documentos</span>
                </button>
            </nav>
        </div>

        {{-- Contenido --}}
        <div class="flex-1 p-6 overflow-y-auto h-full space-y-6">

            {{-- ══ Tab: Empresa ══ --}}
            <div x-show="tab === 'empresa'" x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                 
                 <livewire:settings.settings-company />
                 
            </div>

            {{-- ══ Tab: Documentos ══ --}}
            <div x-show="tab === 'documentos'" x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 style="display:none;">
                 
                 <livewire:settings.settings-documents />
                 
            </div>

        </div>
    </div>
</x-modal>
