@props([
    'name',
])

<x-dynamic-component :component="'lucide-' . $name" {{ $attributes->merge(['class' => 'w-4 h-4 shrink-0 inline-block']) }} />
