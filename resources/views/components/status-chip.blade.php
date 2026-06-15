@props([
    'icon' => null,
    'color' => 'primary', // primary, success, danger, warning, info
])

@php
$iconColor = match($color) {
    'primary' => 'text-primary-500',
    'success' => 'text-success',
    'danger'  => 'text-danger',
    'warning' => 'text-warning',
    'info'    => 'text-info',
    default   => 'text-text-muted',
};
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-[11px] text-text-muted bg-surface-main/60 border border-border/40 rounded-full px-4 py-2 font-medium tracking-wide']) }}>
    @if($icon)
        <x-dynamic-component :component="'lucide-' . $icon" class="w-3.5 h-3.5 shrink-0 {{ $iconColor }}" wire:ignore />
    @endif
    <span class="uppercase">{{ $slot }}</span>
</div>
