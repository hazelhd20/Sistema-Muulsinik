@props([
    'field',
    'label',
    'sortField' => null,
    'sortDirection' => null,
    'align' => 'left'
])

@php
    $isActive = $sortField === $field;
    $icon = 'chevrons-up-down'; // Default
    if ($isActive) {
        $icon = $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down';
    }
    
    $alignClass = match($align) {
        'right' => 'justify-end',
        'center' => 'justify-center',
        default => 'justify-start',
    };
@endphp

<th wire:click="sortBy('{{ $field }}')" class="cursor-pointer group hover:bg-surface-hover/50 transition-colors">
    <div class="flex items-center gap-1.5 {{ $alignClass }}">
        <span class="{{ $isActive ? 'text-text-primary font-semibold' : '' }}">{{ $label }}</span>
        <x-dynamic-component :component="'lucide-' . $icon" 
           class="w-3.5 h-3.5 {{ $isActive ? 'text-primary-600' : 'text-text-muted opacity-0 group-hover:opacity-100 transition-opacity' }}" />
    </div>
</th>
