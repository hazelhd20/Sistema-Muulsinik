@props([
    'title',
    'value',
    'icon' => null,
    'color' => 'primary', // primary, success, danger, warning, info
    'trend' => null, // e.g. +12, -5
    'trendLabel' => 'vs mes anterior',
    'inverseTrend' => false,
    'footer' => null,
    'valueSize' => 'text-h2', // text-h2 or text-h3
    'href' => null,
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

    $isPositiveTrend = $trend !== null && $trend !== 'N/A' && floatval($trend) > 0;
    $isNegativeTrend = $trend !== null && $trend !== 'N/A' && floatval($trend) < 0;

    $trendColorClass = $isPositiveTrend 
        ? ($inverseTrend ? 'text-danger' : 'text-success')
        : ($isNegativeTrend ? ($inverseTrend ? 'text-success' : 'text-danger') : 'text-text-muted');

    $tag = $href ? 'a' : 'div';
    $baseClasses = 'bg-surface-card rounded-2xl border border-border p-5 transition-all duration-200 block';
    if ($href) {
        $baseClasses .= ' hover:border-border-strong hover:shadow-sm cursor-pointer group';
    }
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" wire:navigate @endif {{ $attributes->merge(['class' => $baseClasses]) }}>
    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs-fluid font-semibold text-text-muted uppercase tracking-wider mb-2 @if($href) group-hover:text-text-secondary transition-colors @endif">{{ $title }}</p>
            <p class="{{ $valueSize }} font-bold text-text-primary tabular-nums">{{ $value }}</p>
        </div>
        @if($icon)
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $iconBgColor }} @if($href) group-hover:scale-105 transition-transform @endif">
                <x-dynamic-component :component="'lucide-' . $icon" class="w-5 h-5" />
            </div>
        @endif
    </div>
    
    @if($trend !== null)
        <div class="mt-4 flex items-center gap-1.5 text-xs-fluid">
            @if($trend === 'N/A')
                <span class="flex items-center font-medium text-text-muted">
                    <x-lucide-minus class="w-3.5 h-3.5 mr-1" />
                    N/A
                </span>
                <span class="text-text-muted">sin periodo previo</span>
            @else
                <span class="flex items-center font-medium {{ $trendColorClass }}">
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
            @endif
        </div>
    @endif
    
    @if($footer)
        <div class="mt-4 flex items-center text-xs-fluid text-text-muted font-medium">
            {{ $footer }}
        </div>
    @endif
</{{ $tag }}>
