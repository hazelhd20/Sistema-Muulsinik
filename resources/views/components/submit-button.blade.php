@props([
    'target' => '',
    'text' => 'Guardar'
])

<button type="submit" {{ $attributes->merge(['class' => 'btn-primary relative']) }} wire:loading.attr="disabled" wire:target="{{ $target }}">
    <span wire:loading.class="opacity-0" wire:target="{{ $target }}" class="inline-flex items-center gap-1.5 transition-opacity">
        {{ $slot->isEmpty() ? $text : $slot }}
    </span>
    <span wire:loading wire:target="{{ $target }}" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center justify-center">
        <span class="spinner spinner-sm"></span>
    </span>
</button>
