@props([
    'status' => '',
    'map' => [],
    'dot' => true,
])

@php
    $variant = $map[$status] ?? 'secondary';
@endphp

<span {{ $attributes->class(['badge', "badge-{$variant}"]) }}>
    @if($dot)<span class="badge-dot"></span>@endif
    {{ $slot->isNotEmpty() ? $slot : ucfirst(str_replace('_', ' ', $status)) }}
</span>
