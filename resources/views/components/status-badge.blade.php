@props([
    'status' => '',
    'map' => [],
    'dot' => false,
])

@php
    $statusValue = $status instanceof \BackedEnum ? $status->value : (is_scalar($status) ? $status : '');
    
    // Si el Enum tiene un método color(), lo usamos; si no, buscamos en el mapa.
    if (is_object($status) && method_exists($status, 'color')) {
        $variant = $status->color();
    } else {
        $variant = $map[$statusValue] ?? 'secondary';
    }

    // Si el Enum tiene un método label(), lo usamos. Si no, capitalizamos el string.
    if (is_object($status) && method_exists($status, 'label')) {
        $label = $status->label();
    } else {
        $label = ucfirst(str_replace('_', ' ', (string) $statusValue));
    }
@endphp

<x-badge :variant="$variant" :dot="$dot" {{ $attributes }}>
    {{ $slot->isNotEmpty() ? $slot : $label }}
</x-badge>
