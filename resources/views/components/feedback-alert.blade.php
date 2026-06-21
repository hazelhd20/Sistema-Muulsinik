@props([
    'variant' => 'info', // info, danger, warning, success
    'icon' => null,
])

@php
$colors = match($variant) {
    'danger'  => 'bg-danger-light text-text-primary',
    'warning' => 'bg-warning-light text-text-primary',
    'success' => 'bg-success-light text-text-primary',
    'info'    => 'bg-info-light text-text-primary',
    default   => 'bg-surface-main/50 text-text-primary',
};

$iconColor = match($variant) {
    'danger'  => 'text-danger',
    'warning' => 'text-warning',
    'success' => 'text-success',
    'info'    => 'text-info',
    default   => 'text-text-muted',
};
@endphp

<div {{ $attributes->merge(['class' => "w-full flex gap-3 items-start rounded-xl px-4 py-3.5 text-left {$colors}"]) }}>
    @if($icon)
        <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4 mt-0.5 shrink-0 {{ $iconColor }}" wire:ignore />
    @endif
    <div class="flex-1 text-xs leading-relaxed">
        {{ $slot }}
    </div>
</div>
