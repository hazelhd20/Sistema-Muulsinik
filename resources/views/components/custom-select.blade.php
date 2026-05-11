@props(['options' => [], 'placeholder' => 'Seleccionar...'])

<div
    x-data="{
        open: false,
        search: '',
        value: @entangle($attributes->wire('model')),
        options: {{ json_encode($options) }},
        get selectedLabel() {
            if (this.value === '' || this.value === null) return '{{ $placeholder }}';
            return this.options[this.value] || '{{ $placeholder }}';
        },
        get filteredOptions() {
            if (this.search === '') {
                return this.options;
            }
            const searchLower = this.search.toLowerCase();
            const filtered = {};
            for (const key in this.options) {
                if (this.options[key].toString().toLowerCase().includes(searchLower)) {
                    filtered[key] = this.options[key];
                }
            }
            return filtered;
        }
    }"
    class="relative {{ $attributes->get('class') }}"
    @click.outside="open = false; search = ''"
>
    <!-- Trigger -->
    <button
        type="button"
        @click="open = !open; if(open) { $nextTick(() => $refs.searchInput.focus()) }"
        class="input flex items-center justify-between text-left w-full h-full"
        :class="{ 'border-primary-400 ring-2 ring-primary-50': open }"
    >
        <div class="flex items-center gap-2 truncate">
            {{ $slot }}
            <span x-text="selectedLabel" :class="{ 'text-text-muted': !value }" class="truncate"></span>
        </div>
        <svg class="w-4 h-4 shrink-0 text-text-muted transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Dropdown -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 w-full mt-1 bg-surface-card border border-gray-100 rounded-xl shadow-lg max-h-64 overflow-hidden flex flex-col"
        style="display: none;"
    >
        <!-- Search Input -->
        <div class="p-2 border-b border-gray-100 sticky top-0 bg-surface-card z-10">
            <div class="relative">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-text-muted"></i>
                <input 
                    x-ref="searchInput"
                    type="text" 
                    x-model="search" 
                    placeholder="Buscar..." 
                    class="w-full pl-9 pr-3 py-1.5 text-xs-fluid border border-gray-200 rounded-lg focus:outline-none focus:border-primary-400 focus:ring-1 focus:ring-primary-50"
                    @keydown.escape="open = false; search = ''"
                >
            </div>
        </div>

        <!-- Options List -->
        <div class="overflow-y-auto py-1 flex-1">
            @if($placeholder)
                <div
                    @click="value = ''; open = false; search = ''"
                    x-show="search === ''"
                    class="px-4 py-2.5 text-small cursor-pointer transition-colors"
                    :class="(!value || value == '') ? 'bg-primary-50 text-primary-700 font-medium' : 'text-text-primary hover:bg-surface-hover'"
                >
                    {{ $placeholder }}
                </div>
            @endif

            <template x-for="(label, val) in filteredOptions" :key="val">
                <div
                    @click="value = val; open = false; search = ''"
                    class="px-4 py-2.5 text-small cursor-pointer transition-colors flex items-center justify-between"
                    :class="value == val ? 'bg-primary-50 text-primary-700 font-medium' : 'text-text-primary hover:bg-surface-hover'"
                >
                    <span x-text="label" class="truncate pr-4"></span>
                    <svg x-show="value == val" class="w-4 h-4 shrink-0 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </template>
            
            <div x-show="Object.keys(filteredOptions).length === 0" class="px-4 py-3 text-small text-text-muted text-center">
                No se encontraron resultados
            </div>
        </div>
    </div>
</div>
