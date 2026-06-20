@props(['label', 'value'])

<x-badge 
    variant="secondary" 
    size="md" 
    :dismissible="true" 
    {{ $attributes->merge(['class' => 'shadow-sm hover:border-border-strong']) }}
>
    <span class="text-text-muted">{{ $label }}:</span>
    <span class="text-text-primary font-semibold ml-0.5">{{ $value }}</span>
</x-badge>
