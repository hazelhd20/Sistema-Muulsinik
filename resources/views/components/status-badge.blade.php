@props([
    'status' => '',
    'map' => [],
    'dot' => true,
])

@php
    $variant = $map[$status] ?? 'secondary';
@endphp

<x-badge :variant="$variant" :dot="$dot" {{ $attributes }}>
    {{ $slot->isNotEmpty() ? $slot : ucfirst(str_replace('_', ' ', $status)) }}
</x-badge>
