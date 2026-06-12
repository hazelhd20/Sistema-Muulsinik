@props([
    'placeholder' => 'Seleccionar fecha',
    'options' => '[]',
    'icon' => 'calendar',
    'error' => null
])

<div class="relative w-full"
    x-data="{
        value: @entangle($attributes->wire('model')),
        picker: null,
        init() {
            const initPicker = () => {
                if (typeof window.flatpickr !== 'undefined') {
                    this.picker = window.flatpickr(this.$refs.input, {
                        defaultDate: this.value,
                        dateFormat: 'Y-m-d',
                        ...{{ $options }},
                        onChange: (selectedDates, dateStr) => {
                            this.value = dateStr;
                        },
                        onClose: (selectedDates, dateStr, instance) => {
                            setTimeout(() => {
                                instance.input.blur();
                            }, 0);
                        }
                    });

                    this.$watch('value', (newValue) => {
                        if (newValue !== this.picker.input.value) {
                            this.picker.setDate(newValue);
                        }
                    });
                } else {
                    setTimeout(initPicker, 50);
                }
            };
            initPicker();
        }
    }"
>
    @if($icon)
        <x-dynamic-component :component="'lucide-' . $icon" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted z-10" />
    @endif

    <input 
        x-ref="input"
        type="text" 
        readonly
        class="input w-full bg-surface-card cursor-pointer select-none outline-none focus:ring-0 focus:border-border {{ $icon ? 'pl-9' : '' }} {{ $error ? 'border-danger-400' : '' }}"
        style="-webkit-tap-highlight-color: transparent;"
        placeholder="{{ $placeholder }}"
        {{ $attributes->except(['wire:model', 'class']) }}
    />
</div>
