@props([
    'variant' => 'primary', // primary | secondary | soft | ghost | link
                             // danger | success | warning
                             // soft-primary | soft-danger
                             // ghost-danger
                             // link-muted | link-danger
                             // icon | icon-primary | icon-secondary | icon-danger | icon-success | icon-warning
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'iconRight' => null,
    'target' => null, // para wire:target
    'ariaLabel' => null,
])

@php
    $base = 'inline-flex items-center justify-center transition-all duration-150 cursor-pointer whitespace-nowrap disabled:opacity-55 disabled:cursor-not-allowed disabled:shadow-none relative ';
    
    $fullBtn = $base . 'gap-1.5 px-4 min-h-[2.25rem] text-[length:var(--font-size-small)] rounded-md border tracking-wide shadow-sm ';
    $ghostBtn = $base . 'gap-1.5 px-3 min-h-[2.25rem] text-[length:var(--font-size-small)] rounded-md border border-transparent ';
    $linkBtn = $base . 'gap-1 min-h-[2.25rem] text-[length:var(--font-size-small)] border-none ';
    $iconBtn = $base . 'rounded-[0.4375rem] min-w-[2.25rem] min-h-[2.25rem] border border-transparent ';

    $baseClasses = match($variant) {
        // Solid
        'primary'       => $fullBtn . 'bg-primary-600 text-white font-semibold border-transparent hover:bg-primary-700 hover:ring-4 hover:ring-primary-600/10 active:bg-primary-800 active:shadow-none',
        'secondary'     => $fullBtn . 'bg-surface-card text-text-primary font-medium border-border hover:bg-surface-hover hover:border-border-strong active:bg-surface-hover/70',
        'success'       => $fullBtn . 'bg-success text-white font-semibold border-transparent hover:bg-success-hover hover:ring-4 hover:ring-success/10 active:bg-success-active active:shadow-none',
        'danger'        => $fullBtn . 'bg-danger text-white font-semibold border-transparent hover:bg-danger-hover hover:ring-4 hover:ring-danger/10 active:bg-danger-active active:shadow-none',
        'warning'       => $fullBtn . 'bg-warning text-white font-semibold border-transparent hover:bg-warning-hover hover:ring-4 hover:ring-warning/10 active:bg-warning-active active:shadow-none',
        // Soft
        'soft'          => $fullBtn . 'bg-surface-main text-text-secondary font-medium border-transparent hover:bg-surface-hover hover:text-text-primary active:bg-border/30',
        'soft-primary'  => $fullBtn . 'bg-primary-50 text-primary-700 font-medium border-transparent hover:bg-primary-100 hover:text-primary-800 active:bg-primary-100/80',
        'soft-danger'   => $fullBtn . 'bg-danger-light text-danger font-medium border-transparent hover:bg-danger-light hover:text-danger-hover active:bg-danger-light/80',
        // Ghost
        'ghost'         => $ghostBtn . 'bg-transparent text-text-secondary font-medium hover:bg-surface-hover hover:text-text-primary active:bg-surface-main',
        'ghost-danger'  => $ghostBtn . 'bg-transparent text-text-muted font-medium hover:bg-danger-light hover:text-danger active:bg-danger-light/70',
        // Link
        'link'          => $linkBtn . 'bg-transparent text-primary-600 font-medium hover:text-primary-800 active:text-primary-900',
        'link-muted'    => $linkBtn . 'bg-transparent text-text-muted font-medium hover:text-text-primary',
        'link-danger'   => $linkBtn . 'bg-transparent text-danger font-medium hover:text-danger-hover',
        'link-danger-muted' => $linkBtn . 'bg-transparent text-text-muted font-medium hover:text-danger',
        // Icon
        'icon'          => $iconBtn . 'p-[0.3125rem] text-text-muted bg-transparent hover:bg-slate-900/5 hover:text-text-primary',
        'icon-primary'  => $iconBtn . 'p-[0.3125rem] text-text-muted bg-transparent hover:bg-primary-50 hover:text-primary-600',
        'icon-danger'   => $iconBtn . 'p-[0.3125rem] text-text-muted bg-transparent hover:bg-danger-light hover:text-danger',
        'icon-success'  => $iconBtn . 'p-[0.3125rem] text-text-muted bg-transparent hover:bg-success-light hover:text-success',
        'icon-secondary'=> $iconBtn . 'w-[2.25rem] h-[2.25rem] bg-surface-card text-text-secondary border-border shadow-[0_1px_2px_rgba(0,0,0,0.05)] hover:bg-surface-hover hover:border-border-strong hover:text-text-primary',
        default         => $fullBtn . 'bg-primary-600 text-white font-semibold border-transparent hover:bg-primary-700 hover:ring-4 hover:ring-primary-600/10 active:bg-primary-800 active:shadow-none',
    };

    $isIconButton = str_starts_with($variant, 'icon');
    $isLinkVariant = str_starts_with($variant, 'link');
    $iconClass = $isIconButton ? 'w-4 h-4' : 'w-4 h-4 shrink-0';
    $computedAriaLabel = $ariaLabel ?? $attributes->get('aria-label') ?? $attributes->get('title') ?? ($isIconButton && $slot->isEmpty() ? 'Icon button' : null);
@endphp


@if($href)
    <a href="{!! $href !!}" {{ $attributes->merge(['class' => $baseClasses]) }} @if($computedAriaLabel) aria-label="{{ $computedAriaLabel }}" @endif>
        @if($icon)
            <x-dynamic-component :component="'lucide-' . $icon" class="{{ $iconClass }}" />
        @endif
        @if(!$isIconButton || $slot->isNotEmpty())
            {{ $slot }}
        @endif
        @if($iconRight)
            <x-dynamic-component :component="'lucide-' . $iconRight" class="{{ $iconClass }}" />
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $baseClasses]) }}
        @if($target) wire:loading.attr="disabled" wire:target="{{ $target }}" @endif
        @if($computedAriaLabel) aria-label="{{ $computedAriaLabel }}" @endif>

        @if($target)
            <span wire:loading.class="opacity-0" wire:target="{{ $target }}" class="inline-flex items-center gap-1.5 transition-opacity">
        @endif

        @if($icon)
            <x-dynamic-component :component="'lucide-' . $icon" class="{{ $iconClass }}" />
        @endif

        @if(!$isIconButton || $slot->isNotEmpty())
            {{ $slot }}
        @endif

        @if($iconRight)
            <x-dynamic-component :component="'lucide-' . $iconRight" class="{{ $iconClass }}" />
        @endif

        @if($target)
            </span>
            <span wire:loading wire:target="{{ $target }}" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center justify-center">
                <span class="spinner spinner-sm"></span>
            </span>
        @endif
    </button>
@endif
