@props([
    'title',
    'value',
    'icon' => null,
    'color' => 'primary', // primary, success, danger, warning, info
    'trend' => null, // e.g. +12, -5
    'trendLabel' => 'vs mes anterior',
    'valueSize' => 'text-h2', // text-h2 or text-h3
])

@php
    $iconBgColors = [
        'primary' => 'bg-primary-50 text-primary-600',
        'success' => 'bg-success-light text-success',
        'danger' => 'bg-danger-light text-danger',
        'warning' => 'bg-warning-light text-warning',
        'info' => 'bg-info-light text-info',
    ];
    $iconBgColor = $iconBgColors[$color] ?? $iconBgColors['primary'];

    $isPositiveTrend = $trend !== null && floatval($trend) > 0;
    $isNegativeTrend = $trend !== null && floatval($trend) < 0;
@endphp

<div {{ $attributes->merge(['class' => 'bg-surface-card rounded-2xl border border-border p-5 shadow-sm hover:shadow-md transition-shadow']) }}>
    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-2">{{ $title }}</p>
            <p class="{{ $valueSize }} font-bold text-text-primary tabular-nums">{{ $value }}</p>
        </div>
        @if($icon)
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $iconBgColor }}">
                <x-dynamic-component :component="'lucide-' . $icon" class="w-5 h-5" />
            </div>
        @endif
    </div>
    
    @if($trend !== null)
        <div class="mt-4 flex items-center gap-1.5 text-xs">
            <span class="flex items-center font-medium {{ $isPositiveTrend ? 'text-success' : ($isNegativeTrend ? 'text-danger' : 'text-text-muted') }}">
                @if($isPositiveTrend)
                    <x-lucide-trending-up class="w-3.5 h-3.5 mr-1" />
                @elseif($isNegativeTrend)
                    <x-lucide-trending-down class="w-3.5 h-3.5 mr-1" />
                @else
                    <x-lucide-minus class="w-3.5 h-3.5 mr-1" />
                @endif
                {{ $trend > 0 ? '+' : '' }}{{ $trend }}%
            </span>
            <span class="text-text-muted">{{ $trendLabel }}</span>
        </div>
    @endif
    
    @if(isset($footer))
        <div class="mt-4 pt-4 border-t border-border">
            {{ $footer }}
        </div>
    @endif
</div>
