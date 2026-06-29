@props(['options' => [], 'placeholder' => 'Seleccionar...', 'textClass' => '', 'minSearch' => 10, 'size' => 'md'])

<div
    x-data="{
        open: false,
        search: '',
        value: '',
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
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                if (Object.keys(this.options).length >= {{ $minSearch }}) {
                    this.$nextTick(() => this.$refs.searchInput.focus());
                }
            }
        },
        close() {
            this.open = false;
            this.search = '';
        }
    }"
    x-modelable="value"
    {!! $attributes->whereStartsWith('wire:model') !!}
    {!! $attributes->whereStartsWith('x-model') !!}
    {{ $attributes->except(['wire:model', 'wire:model.live', 'wire:model.defer', 'wire:model.blur', 'x-model', 'class']) }}
    class="relative {{ $attributes->get('class') }}"
>
    <!-- Trigger -->
    <button
        x-ref="trigger"
        type="button"
        @click="toggle()"
        role="combobox"
        aria-haspopup="listbox"
        :aria-expanded="open.toString()"
        class="input flex items-center justify-between text-left w-full {{ $size === 'sm' ? '!h-8 !py-1 !px-2.5 !text-xs' : '' }}"
        :class="{ 'border-primary-400 ring-2 ring-primary-50': open }"
    >
        <div class="flex items-center gap-2 truncate {{ $textClass }}">
            {{ $slot }}
            <span x-text="selectedLabel" :class="{ 'text-text-muted': !value }" class="truncate"></span>
        </div>
        <svg class="w-4 h-4 shrink-0 text-text-muted transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Dropdown (Teleported) -->
    <template x-teleport="body">
        <div
            x-show="open"
            @click.outside="if (! $refs.trigger.contains($event.target)) close()"
            @click.stop
            x-anchor.bottom-start.offset.4="$refs.trigger"
            x-transition:enter="transition-premium"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition-premium"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            :style="{ minWidth: $refs.trigger?.offsetWidth + 'px' }"
            class="z-[200] bg-surface-card border border-border rounded-xl shadow-xl flex flex-col max-w-[90vw] max-h-64 overflow-hidden mt-1"
            style="display: none;"
        >
        <!-- Search Input -->
        <div x-show="Object.keys(options).length >= {{ $minSearch }}" class="dropdown-header sticky top-0 z-10 p-2">
            <div class="relative w-full">
                <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-text-muted" />
                <input 
                    x-ref="searchInput"
                    type="text" 
                    x-model="search" 
                    placeholder="Buscar..." 
                    class="w-full pl-9 pr-3 py-1.5 text-xs border border-border rounded-lg focus:outline-none focus:border-primary-400 focus:ring-1 focus:ring-primary-50"
                    @keydown.escape="close()"
                >
            </div>
        </div>

        <!-- Options List -->
        <div class="overflow-y-auto flex-1" role="listbox">
            @if($placeholder)
                <div
                    @click="value = ''; close()"
                    x-show="search === ''"
                    role="option"
                    :aria-selected="(!value || value == '') ? 'true' : 'false'"
                    class="cursor-pointer transition-colors {{ $size === 'sm' ? 'px-3 py-1.5 text-xs' : 'px-4 py-2.5 text-small' }}"
                    :class="(!value || value == '') ? 'bg-primary-50 text-primary-700 font-medium' : 'text-text-primary hover:bg-surface-hover'"
                >
                    {{ $placeholder }}
                </div>
            @endif

            <template x-for="(label, val) in filteredOptions" :key="val">
                <div
                    @click="value = val; close()"
                    role="option"
                    :aria-selected="value == val ? 'true' : 'false'"
                    class="cursor-pointer transition-colors flex items-center justify-between {{ $size === 'sm' ? 'px-3 py-1.5 text-xs' : 'px-4 py-2.5 text-small' }}"
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
    </template>
</div>
