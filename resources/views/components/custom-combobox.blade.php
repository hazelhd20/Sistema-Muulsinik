@props(['options' => [], 'placeholder' => 'Buscar o escribir...', 'icon' => null, 'iconPosition' => 'left', 'inputClass' => ''])

<div
    x-data="{
        open: false,
        value: @entangle($attributes->wire('model')).live,
        options: {{ json_encode($options) }},
        activeIndex: -1,
        get normalizedOptions() {
            if (Array.isArray(this.options)) {
                return this.options.map(opt => ({ value: String(opt), label: String(opt) }));
            } else if (typeof this.options === 'object' && this.options !== null) {
                return Object.keys(this.options).map(key => ({ value: String(key), label: String(this.options[key]) }));
            }
            return [];
        },
        get filteredOptions() {
            if (!this.value || this.value.trim() === '') {
                return this.normalizedOptions;
            }
            const searchLower = this.value.toLowerCase();
            let matches = this.normalizedOptions.filter(opt => 
                opt.label.toLowerCase().includes(searchLower) || opt.value.toLowerCase().includes(searchLower)
            );
            
            // Sort to prioritize exact value matches and prefix matches
            matches.sort((a, b) => {
                const aVal = a.value.toLowerCase();
                const bVal = b.value.toLowerCase();
                const aLabel = a.label.toLowerCase();
                const bLabel = b.label.toLowerCase();
                
                // 1. Exact value match (e.g. 'm' for Metro)
                if (aVal === searchLower) return -1;
                if (bVal === searchLower) return 1;
                
                // 2. Value prefix match (e.g. 'm...' for value)
                const aValPrefix = aVal.startsWith(searchLower);
                const bValPrefix = bVal.startsWith(searchLower);
                if (aValPrefix && !bValPrefix) return -1;
                if (!aValPrefix && bValPrefix) return 1;
                
                // 3. Label prefix match (e.g. 'M...' for Metro)
                const aLabelPrefix = aLabel.startsWith(searchLower);
                const bLabelPrefix = bLabel.startsWith(searchLower);
                if (aLabelPrefix && !bLabelPrefix) return -1;
                if (!aLabelPrefix && bLabelPrefix) return 1;
                
                return 0; // Keep original order otherwise
            });
            
            return matches;
        },
        highlight(text) {
            if (!this.value || this.value.trim() === '') return text;
            const searchLower = this.value.toLowerCase();
            const lowerText = text.toLowerCase();
            const index = lowerText.indexOf(searchLower);
            if (index === -1) return text;
            return text.substring(0, index) + '<strong class=\'font-bold text-primary-600\'>' + text.substring(index, index + searchLower.length) + '</strong>' + text.substring(index + searchLower.length);
        },
        openDropdown() {
            this.open = true;
            this.activeIndex = -1;
        },
        closeDropdown() {
            this.open = false;
            this.activeIndex = -1;
        },
        selectOption(opt) {
            this.value = opt.value;
            this.closeDropdown();
            this.$refs.input.focus();
        },
        selectActive() {
            if (this.activeIndex >= 0 && this.activeIndex < this.filteredOptions.length) {
                this.selectOption(this.filteredOptions[this.activeIndex]);
            } else {
                this.closeDropdown(); // if they press enter on empty state or custom text
            }
        },
        moveDown() {
            if (!this.open) {
                this.openDropdown();
                return;
            }
            if (this.activeIndex < this.filteredOptions.length - 1) {
                this.activeIndex++;
                this.scrollToActive();
            }
        },
        moveUp() {
            if (!this.open) {
                this.openDropdown();
                return;
            }
            if (this.activeIndex > 0) {
                this.activeIndex--;
                this.scrollToActive();
            }
        },
        scrollToActive() {
            this.$nextTick(() => {
                const list = this.$refs.listbox;
                if (!list) return;
                const options = list.querySelectorAll('[data-option]');
                const activeEl = options[this.activeIndex];
                if (activeEl) {
                    const listRect = list.getBoundingClientRect();
                    const elRect = activeEl.getBoundingClientRect();
                    if (elRect.bottom > listRect.bottom) {
                        list.scrollTop += elRect.bottom - listRect.bottom;
                    } else if (elRect.top < listRect.top) {
                        list.scrollTop -= listRect.top - elRect.top;
                    }
                }
            });
        }
    }"
    class="relative {{ $attributes->get('class') }}"
>
    <!-- Input -->
    <div class="relative w-full">
        @if($icon && $iconPosition === 'left')
            <x-dynamic-component :component="'lucide-' . $icon" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
        @endif
        
        <input 
            x-ref="input"
            type="text"
            role="combobox"
            aria-autocomplete="list"
            aria-haspopup="listbox"
            :aria-expanded="open.toString()"
            x-model="value"
            @focus="openDropdown()"
            @input="openDropdown()"
            @keydown.escape="closeDropdown()"
            @keydown.arrow-down.prevent="moveDown()"
            @keydown.arrow-up.prevent="moveUp()"
            @keydown.enter.prevent="selectActive()"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            class="input w-full h-9 {{ $icon && $iconPosition === 'left' ? 'pl-9' : '' }} {{ $icon && $iconPosition === 'right' ? 'pr-9' : '' }} {{ $inputClass }}"
            :class="{ 'border-primary-400 ring-2 ring-primary-50': open }"
        >
        
        @if($icon && $iconPosition === 'right')
            <x-dynamic-component :component="'lucide-' . $icon" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted pointer-events-none" />
        @endif

        {{ $slot }} <!-- Para inyectar iconos de validacion/status -->
    </div>

    <!-- Dropdown -->
    <template x-teleport="body">
        <div
            x-show="open"
            @click.outside="if (! $refs.input.contains($event.target)) closeDropdown()"
            @click.stop
            x-anchor.bottom-start.offset.4="$refs.input"
            x-transition:enter="transition-premium"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition-premium"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            :style="{ minWidth: $refs.input?.offsetWidth + 'px' }"
            class="z-[200] bg-surface-card border border-border rounded-xl shadow-xl flex flex-col max-w-[90vw] max-h-64 overflow-hidden mt-1"
            style="display: none;"
        @if(isset($header))
            <div class="dropdown-header">
                {{ $header }}
            </div>
        @endif

        <div class="py-1 flex-1 overflow-y-auto" x-ref="listbox" role="listbox">
            <template x-if="filteredOptions.length > 0">
                <template x-for="(opt, index) in filteredOptions" :key="index">
                    <div
                        data-option
                        role="option"
                        :aria-selected="value == opt.value ? 'true' : 'false'"
                        @click="selectOption(opt)"
                        @mouseenter="activeIndex = index"
                        class="px-4 py-2.5 text-small cursor-pointer transition-colors"
                        :class="{ 'bg-primary-50 text-primary-900': activeIndex === index, 'text-text-primary hover:bg-zinc-100': activeIndex !== index }"
                    >
                        <span x-html="highlight(opt.label)" class="truncate"></span>
                    </div>
                </template>
            </template>
            
            <template x-if="filteredOptions.length === 0">
                <div class="px-4 py-3 text-center text-small text-text-muted">
                    <p>No se encontraron resultados.</p>
                    <p class="text-[10px] mt-1 text-primary-600">Presiona Enter para usar "<span x-text="value" class="font-semibold"></span>"</p>
                </div>
            </template>
        </div>

        @if(isset($footer))
            <div class="dropdown-footer">
                {{ $footer }}
            </div>
        @endif
        </div>
    </template>
</div>
