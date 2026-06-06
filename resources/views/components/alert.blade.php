@props([
    'type' => 'info', // success, danger, warning, info
    'message' => '',
])

@php
    $icons = [
        'success' => 'check-circle',
        'danger' => 'alert-circle',
        'warning' => 'alert-triangle',
        'info' => 'info',
    ];

    $colors = [
        'success' => 'bg-success-light text-success-active border border-success-border',
        'danger' => 'bg-danger-light text-danger-active border border-danger-border',
        'warning' => 'bg-warning-light text-warning-active border border-warning-border',
        'info' => 'bg-info-light text-info-active border border-info-border',
    ];

    $iconColor = [
        'success' => 'text-success',
        'danger' => 'text-danger',
        'warning' => 'text-warning',
        'info' => 'text-info',
    ];

    $currentIcon = $icons[$type] ?? $icons['info'];
    $currentColor = $colors[$type] ?? $colors['info'];
    $currentIconColor = $iconColor[$type] ?? $iconColor['info'];
@endphp

<div {{ $attributes->merge(['class' => "p-3 rounded-lg text-small flex items-start gap-2.5 shadow-sm $currentColor"]) }} role="alert">
    <i data-lucide="{{ $currentIcon }}" class="w-4 h-4 shrink-0 mt-0.5 {{ $currentIconColor }}"></i>
    <div class="flex-1">
        @if($message)
            <span>{{ $message }}</span>
        @else
            {{ $slot }}
        @endif
    </div>
</div>
