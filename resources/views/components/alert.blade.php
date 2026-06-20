@props([
    'type' => 'info', // success, danger, warning, info
    'message' => '',
    'dismissible' => false,
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

<div x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms {{ $attributes->merge(['class' => "p-3 rounded-lg text-small flex items-start gap-2.5 shadow-sm $currentColor"]) }} role="alert">
    <x-dynamic-component :component="'lucide-' . $currentIcon" class="w-4 h-4 shrink-0 mt-0.5 {{ $currentIconColor }}" />
    <div class="flex-1">
        @if($message)
            <span>{{ $message }}</span>
        @else
            {{ $slot }}
        @endif
    </div>
    @if($dismissible)
        <button type="button" @click="show = false" class="btn-icon shrink-0 -mr-1 -mt-1 w-6 h-6 p-0 opacity-70 hover:opacity-100" aria-label="Cerrar alerta">
            <x-lucide-x class="w-4 h-4" />
        </button>
    @endif
</div>
