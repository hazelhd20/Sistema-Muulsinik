@props([
    'type' => 'info', // success, danger, warning, info
    'variant' => null, // alias for type
    'title' => '',
    'message' => '',
    'icon' => null,
    'dismissible' => false,
])

@php
    $type = $variant ?? $type;
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

    $titleColor = [
        'success' => 'text-success-active',
        'danger' => 'text-danger-active',
        'warning' => 'text-warning-active',
        'info' => 'text-info-active',
    ];

    $currentIcon = $icon ?? $icons[$type] ?? $icons['info'];
    $currentColor = $colors[$type] ?? $colors['info'];
    $currentIconColor = $iconColor[$type] ?? $iconColor['info'];
    $currentTitleColor = $titleColor[$type] ?? $titleColor['info'];
@endphp

<div x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms {{ $attributes->merge(['class' => "p-4 rounded-xl flex items-start gap-3 $currentColor"]) }} role="alert">
    <x-dynamic-component :component="'lucide-' . $currentIcon" class="w-5 h-5 shrink-0 mt-0.5 {{ $currentIconColor }}" aria-hidden="true" />
    <div class="flex-1 min-w-0">
        @if($title)
            <h4 class="text-small font-semibold {{ $currentTitleColor }}">{{ $title }}</h4>
        @endif
        
        <div class="{{ $title ? 'mt-1 opacity-90 text-sm' : 'text-sm font-medium' }}">
            @if($message)
                <span>{{ $message }}</span>
            @else
                {{ $slot }}
            @endif
        </div>

        @if(isset($footer))
            <div class="mt-2 text-xs opacity-75">
                {{ $footer }}
            </div>
        @endif
    </div>
    @if($dismissible)
        <button type="button" @click="show = false" class="btn-close -mr-1 -mt-1" aria-label="Cerrar alerta">
            <x-lucide-x class="w-4 h-4" />
        </button>
    @endif
</div>
