@props([
    'label' => null,
    'description' => null,
    'id' => null,
])

@php
    $id = $id ?? $attributes->whereStartsWith('wire:model')->first() ?? uniqid('toggle-');
@endphp

<div class="flex items-start gap-3">
    <label for="{{ $id }}" class="group relative flex items-center cursor-pointer mt-0.5 has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-60">
        <input 
            type="checkbox"
            id="{{ $id }}"
            class="peer sr-only"
            {{ $attributes }}
        >
        <div class="h-5 w-9 rounded-full bg-border peer-checked:bg-primary-600 transition-colors duration-200 ease-in-out peer-focus-visible:ring-2 peer-focus-visible:ring-primary-500 peer-focus-visible:ring-offset-2 peer-disabled:opacity-50 peer-disabled:cursor-not-allowed"></div>
        <div class="absolute left-0.5 h-4 w-4 rounded-full bg-surface-card shadow transition-transform duration-200 ease-in-out peer-checked:translate-x-[16px]"></div>
    </label>
    
    @if($label || $description)
        <div class="flex flex-col min-w-0">
            @if($label)
                <label for="{{ $id }}" class="text-sm font-medium text-text-primary cursor-pointer select-none">
                    {{ $label }}
                </label>
            @endif
            @if($description)
                <p class="text-xs text-text-muted mt-0.5">
                    {{ $description }}
                </p>
            @endif
        </div>
    @endif
</div>
