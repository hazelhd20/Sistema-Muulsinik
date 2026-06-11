@props([
    'url',
    'icon',
    'title',
    'subtitle' => null,
    'color' => 'primary', // primary, success, warning, info
    'typeLabel' => null,
])

@php
    $bgClass = match($color) {
        'success' => 'bg-success-light',
        'warning' => 'bg-warning-light',
        'info'    => 'bg-info-light',
        default   => 'bg-primary-50',
    };
    $textClass = match($color) {
        'success' => 'text-success',
        'warning' => 'text-warning',
        'info'    => 'text-info',
        default   => 'text-primary-600',
    };
@endphp

<a href="{{ $url }}" wire:navigate {{ $attributes->merge(['class' => 'flex items-center gap-3 px-4 py-2.5 hover:bg-surface-hover transition-colors group rounded-md cursor-pointer']) }}>
    <div class="w-8 h-8 rounded-lg {{ $bgClass }} flex items-center justify-center shrink-0">
        <x-dynamic-component :component="'lucide-' . $icon" class="w-4 h-4 {{ $textClass }}" />
    </div>
    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
            <p class="text-small font-medium text-text-primary truncate group-hover:text-primary-600 transition-colors">{!! $title !!}</p>
            @if($typeLabel)
                <span class="badge badge-secondary shrink-0 text-[0.6rem] py-0.5 px-1.5 opacity-60">{{ $typeLabel }}</span>
            @endif
        </div>
        @if(!empty($subtitle))
            <p class="text-xs-fluid text-text-muted truncate">{!! $subtitle !!}</p>
        @endif
    </div>
    <x-lucide-arrow-right class="w-3.5 h-3.5 text-text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
</a>
