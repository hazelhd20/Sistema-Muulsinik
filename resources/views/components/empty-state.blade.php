@props([
    'icon' => 'inbox', 
    'title' => '', 
    'message' => '',
    'description' => null,
    'variant' => 'inline', // inline, page, search
])

@php
    $containerClasses = match($variant) {
        'page' => 'flex flex-col items-center justify-center py-16 md:py-24 text-center px-4',
        'search' => 'flex flex-col items-center justify-center py-10 text-center px-4',
        default => 'flex flex-col items-center justify-center py-12 text-center px-4',
    };
    
    $iconContainerClasses = match($variant) {
        'page' => 'w-20 h-20 rounded-2xl bg-surface-hover flex items-center justify-center mb-6 border border-border/50 shadow-sm',
        'search' => 'w-12 h-12 rounded-full bg-surface-main flex items-center justify-center mb-4',
        default => 'w-12 h-12 rounded-xl bg-surface-main flex items-center justify-center mb-3',
    };
    
    $iconClasses = match($variant) {
        'page' => 'w-10 h-10 text-primary-400',
        'search' => 'w-5 h-5 text-text-muted opacity-70',
        default => 'w-6 h-6 text-text-muted opacity-50',
    };
    
    $titleClasses = match($variant) {
        'page' => 'text-h2 font-bold text-text-primary mb-2',
        default => 'text-small font-semibold text-text-primary mb-1',
    };
    
    $descClasses = match($variant) {
        'page' => 'text-sm text-text-secondary max-w-md leading-relaxed',
        default => 'text-xs text-text-secondary max-w-sm',
    };
@endphp

<div {{ $attributes->merge(['class' => $containerClasses]) }}>
    <div class="{{ $iconContainerClasses }}">
        <x-dynamic-component :component="'lucide-' . $icon" class="{{ $iconClasses }}" stroke-width="{{ $variant === 'page' ? '1.5' : '2' }}" />
    </div>
    
    @if($title)
        <h3 class="{{ $titleClasses }}">{{ $title }}</h3>
    @endif
    
    @if($description || $message)
        <div class="{{ $descClasses }}">{{ $description ?? $message }}</div>
    @endif
    
    @if(isset($actions))
        <div class="mt-6 flex items-center gap-3 justify-center">{{ $actions }}</div>
    @elseif($slot->isNotEmpty())
        <div class="mt-6 flex items-center gap-3 justify-center">{{ $slot }}</div>
    @endif
</div>
