@props(['label', 'value'])

<x-badge variant="secondary" size="md" dismissible {{ $attributes->except(['class']) }} class="shadow-sm hover:border-border-strong {{ $attributes->get('class') }}">
    <span class="text-text-muted">{{ $label }}:</span>
    <span class="text-text-primary font-semibold ml-0.5">{{ $value }}</span>
</x-badge>
