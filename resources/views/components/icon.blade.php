@props([
    'name',
])

<i data-lucide="{{ $name }}" {{ $attributes->merge(['class' => 'w-4 h-4 shrink-0 inline-block']) }}></i>
