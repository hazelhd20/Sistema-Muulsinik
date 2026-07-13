@props([
    'placeholder' => 'Seleccionar fecha',
    'options' => [],
    'icon' => 'calendar',
    'error' => null
])

<div class="relative w-full"
    x-data="datePicker({ 
        value: @if($attributes->whereStartsWith('wire:model')->first()) @entangle($attributes->wire('model')) @else '' @endif, 
        options: {{ json_encode($options) }} 
    })"
    {!! $attributes->whereStartsWith('x-model') !!}
    x-modelable="value"
    wire:ignore
>
    @if($icon)
        <x-dynamic-component :component="'lucide-' . $icon" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted z-10" />
    @endif

    <input 
        @if($attributes->get('id') || $attributes->whereStartsWith('wire:model')->first() || $attributes->whereStartsWith('x-model')->first())
            id="{{ $attributes->get('id') ?? $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first() }}"
        @endif
        aria-label="{{ $attributes->get('aria-label') ?? $placeholder }}"
        x-ref="input"
        type="text" 
        readonly
        class="input w-full bg-surface-card cursor-pointer select-none outline-none focus:ring-0 focus:border-border {{ $icon ? 'pl-9' : '' }} {{ $error ? 'border-danger-400' : '' }}"
        style="-webkit-tap-highlight-color: transparent;"
        placeholder="{{ $placeholder }}"
        {{ $attributes->except(['wire:model', 'class', 'x-model']) }}
    />
</div>
