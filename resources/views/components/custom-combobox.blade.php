@props(['options' => [], 'placeholder' => 'Buscar o escribir...', 'icon' => null, 'iconPosition' => 'left', 'inputClass' => ''])

<div
    x-data="{
        open: false,
        value: @entangle($attributes->wire('model')).live,
        options: {{ json_encode($options) }},
        dropStyle: {},
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
        reposition() {
            const rect = this.$refs.input.getBoundingClientRect();
            const maxH = 200;
            const spaceBelow = window.innerHeight - rect.bottom - 8;
            const openUp = spaceBelow < maxH && rect.top > spaceBelow;
            this.dropStyle = {
                position: 'fixed',
                left: rect.left + 'px',
                minWidth: rect.width + 'px',
                width: 'max-content',
                maxWidth: 'min(400px, 90vw)',
                maxHeight: Math.min(maxH, openUp ? rect.top - 8 : spaceBelow) + 'px',
                ...(openUp
                    ? { bottom: (window.innerHeight - rect.top + 4) + 'px', top: 'auto' }
                    : { top: (rect.bottom + 4) + 'px', bottom: 'auto' }),
            };
        },
        openDropdown() {
            this.open = true;
            this.activeIndex = -1;
            this.$nextTick(() => this.reposition());
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
        },
        _scrollHandler: null,
        init() {
            this._scrollHandler = () => { if (this.open) this.reposition(); };
            document.addEventListener('scroll', this._scrollHandler, true);
        },
        destroy() {
            if (this._scrollHandler) document.removeEventListener('scroll', this._scrollHandler, true);
        }
    }"
    class="relative {{ $attributes->get('class') }}"
    @click.outside="closeDropdown()"
    @resize.window="if(open) reposition()"
>
    <!-- Input -->
    <div class="relative w-full">
        @if($icon && $iconPosition === 'left')
            <i data-lucide="{{ $icon }}" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
        @endif
        
        <input 
            x-ref="input"
            type="text"
            x-model="value"
            @focus="openDropdown()"
            @input="openDropdown()"
            @keydown.escape="closeDropdown()"
            @keydown.arrow-down.prevent="moveDown()"
            @keydown.arrow-up.prevent="moveUp()"
            @keydown.enter.prevent="selectActive()"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            class="input w-full {{ $icon && $iconPosition === 'left' ? 'pl-9' : '' }} {{ $icon && $iconPosition === 'right' ? 'pr-9' : '' }} {{ $inputClass }}"
            :class="{ 'border-primary-400 ring-2 ring-primary-50': open }"
        >
        
        @if($icon && $iconPosition === 'right')
            <i data-lucide="{{ $icon }}" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted pointer-events-none"></i>
        @endif

        {{ $slot }} <!-- Para inyectar iconos de validacion/status -->
    </div>

    <!-- Dropdown -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        :style="dropStyle"
        class="z-[200] bg-surface-card border border-gray-100 rounded-xl shadow-lg overflow-y-auto flex flex-col"
        style="display: none;"
    >
        <div class="py-1" x-ref="listbox">
            <template x-if="filteredOptions.length > 0">
                <template x-for="(opt, index) in filteredOptions" :key="index">
                    <div
                        data-option
                        @click="selectOption(opt)"
                        @mouseenter="activeIndex = index"
                        class="px-4 py-2.5 text-small cursor-pointer transition-colors"
                        :class="{ 'bg-primary-50 text-primary-900': activeIndex === index, 'text-text-primary hover:bg-surface-hover': activeIndex !== index }"
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
    </div>
</div>
