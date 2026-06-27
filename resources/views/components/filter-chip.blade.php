@props(['label', 'value'])

<x-badge 
    variant="secondary" 
    size="md" 
    :dismissible="true" 
    {{ $attributes }}
>
    <span class="text-text-muted">{{ $label }}:</span>
    <span class="text-text-primary font-semibold ml-0.5">{{ $value }}</span>
</x-badge>
