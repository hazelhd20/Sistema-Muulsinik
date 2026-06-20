@props([
    'icon' => null,
    'color' => 'primary', // primary, success, danger, warning, info
])

<x-badge :variant="'soft-' . $color" size="lg" :icon="$icon" {{ $attributes->merge(['class' => 'uppercase tracking-wide font-bold']) }}>
    {{ $slot }}
</x-badge>
