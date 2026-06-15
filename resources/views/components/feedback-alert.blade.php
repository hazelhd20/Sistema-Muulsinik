@props([
    'variant' => 'info', // info, danger, warning, success
    'icon' => null,
])

@php
$colors = match($variant) {
    'danger'  => 'bg-danger-50/50 border-danger-border/60 text-danger-800',
    'warning' => 'bg-warning-50/50 border-warning-border/60 text-warning-800',
    'success' => 'bg-success-50/50 border-success-border/60 text-success-800',
    'info'    => 'bg-info-50/50 border-info-border/60 text-info-800',
    default   => 'bg-surface-main/50 border-border/60 text-text-primary',
};

$iconColor = match($variant) {
    'danger'  => 'text-danger',
    'warning' => 'text-warning',
    'success' => 'text-success',
    'info'    => 'text-info',
    default   => 'text-text-muted',
};
@endphp

<div {{ $attributes->merge(['class' => "w-full flex gap-3 items-start border rounded-xl px-4 py-3.5 text-left {$colors}"]) }}>
    @if($icon)
        <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4 mt-0.5 shrink-0 {{ $iconColor }}" wire:ignore />
    @endif
    <div class="flex-1 text-xs leading-relaxed">
        {{ $slot }}
    </div>
</div>
