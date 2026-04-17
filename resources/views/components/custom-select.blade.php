@props(['options' => [], 'placeholder' => 'Seleccionar...'])

<div
    x-data="{
        open: false,
        value: @entangle($attributes->wire('model')),
        options: {{ json_encode($options) }},
        get selectedLabel() {
            if (this.value === '' || this.value === null) return '{{ $placeholder }}';
            return this.options[this.value] || '{{ $placeholder }}';
        }
    }"
    class="relative {{ $attributes->get('class') }}"
    @click.outside="open = false"
>
    <!-- Trigger -->
    <button
        type="button"
        @click="open = !open"
        class="input flex items-center justify-between text-left w-full h-full"
        :class="{ 'border-primary-400 ring-2 ring-primary-100': open }"
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
        class="absolute z-50 w-full mt-1 bg-surface-card border border-gray-100 rounded-xl shadow-lg max-h-60 overflow-auto py-1"
        style="display: none;"
    >
        @if($placeholder)
            <div
                @click="value = ''; open = false"
                class="px-4 py-2.5 text-sm cursor-pointer transition-colors"
                :class="(!value || value == '') ? 'bg-primary-50 text-primary-700 font-medium' : 'text-text-primary hover:bg-surface-hover'"
            >
                {{ $placeholder }}
            </div>
        @endif

        <template x-for="(label, val) in options" :key="val">
            <div
                @click="value = val; open = false"
                class="px-4 py-2.5 text-sm cursor-pointer transition-colors flex items-center justify-between"
                :class="value == val ? 'bg-primary-50 text-primary-700 font-medium' : 'text-text-primary hover:bg-surface-hover'"
            >
                <span x-text="label" class="truncate pr-4"></span>
                <svg x-show="value == val" class="w-4 h-4 shrink-0 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </template>
    </div>
</div>
