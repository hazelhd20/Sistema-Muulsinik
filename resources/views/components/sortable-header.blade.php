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

    $alignClass = match ($align) {
        'right' => 'justify-end',
        'center' => 'justify-center',
        default => 'justify-start',
    };
@endphp


<th wire:click="sortBy('{{ $field }}')" 
    {{ $attributes->merge(['class' => 'cursor-pointer select-none group transition-colors duration-150 text-xs-fluid font-semibold uppercase tracking-wider']) }}>
    <div class="flex items-center gap-1.5 py-1 {{ $alignClass }}">
        <span class="{{ $isActive ? 'text-text-primary font-bold' : 'text-text-muted group-hover:text-text-primary' }} transition-colors">{{ $label }}</span>
        <span class="inline-flex items-center justify-center p-1 rounded-md transition-all duration-150 {{ $isActive ? 'bg-primary-500/10 text-primary-600 dark:text-primary-400 opacity-100' : 'text-text-muted opacity-40 group-hover:opacity-100 group-hover:bg-surface-hover group-hover:text-text-primary' }}">
            <x-dynamic-component :component="'lucide-' . $icon" class="w-3.5 h-3.5 shrink-0" />
        </span>
    </div>
</th>
