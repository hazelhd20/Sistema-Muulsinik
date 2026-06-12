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
        class="input w-full bg-surface-card {{ $icon ? 'pl-9' : '' }} {{ $error ? 'border-danger-400 focus:border-danger-500 focus:ring-danger-50' : 'focus:border-primary-400 focus:ring-primary-50' }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->except(['wire:model', 'class']) }}
    />
</div>
