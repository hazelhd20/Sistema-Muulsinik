@props([
    'align' => 'left'
])
@php
    $alignClass = match ($align) {
        'right' => 'justify-end',
        'center' => 'justify-center',
        default => 'justify-start',
    };
    $textClass = match ($align) {
        'right' => 'text-right',
        'center' => 'text-center',
        default => 'text-left',
    };
@endphp

<th {{ $attributes->merge(['class' => 'select-none ' . $textClass]) }}>
    <div class="flex items-center {{ $alignClass }}">
        <span>{{ $slot }}</span>
    </div>
</th>
