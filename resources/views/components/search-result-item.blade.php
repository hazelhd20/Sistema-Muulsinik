@props([
    'url',
    'icon',
    'title',
    'subtitle',
    'color' => 'primary', // primary, success, warning, info
    'isMobile' => false
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

@if($isMobile)
    <a href="{{ $url }}" wire:navigate @click="open = false" class="flex items-center gap-3 px-4 py-3 border-b border-border hover:bg-surface-hover">
        <i data-lucide="{{ $icon }}" class="w-4 h-4 {{ $textClass }}"></i>
        <div>
            <p class="text-small font-medium text-text-primary">{{ $title }}</p>
            <p class="text-xs-fluid text-text-muted">{{ $subtitle }}</p>
        </div>
    </a>
@else
    <a href="{{ $url }}" wire:navigate class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-hover transition-colors group">
        <div class="w-8 h-8 rounded-lg {{ $bgClass }} flex items-center justify-center shrink-0">
            <i data-lucide="{{ $icon }}" class="w-4 h-4 {{ $textClass }}"></i>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-small font-medium text-text-primary truncate group-hover:text-primary-600 transition-colors">{{ $title }}</p>
            <p class="text-xs-fluid text-text-muted truncate">{{ $subtitle }}</p>
        </div>
        <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-text-muted opacity-0 group-hover:opacity-100 transition-opacity"></i>
    </a>
@endif
