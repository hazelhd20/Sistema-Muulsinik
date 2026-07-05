@props([
    'variant' => 'primary', // primary | secondary | soft | ghost | link
                             // danger | success | warning
                             // soft-primary | soft-danger | soft-success
                             // ghost-danger
                             // link-muted | link-danger
                             // icon | icon-primary | icon-secondary | icon-danger | icon-success | icon-warning
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'iconRight' => null,
    'target' => null, // para wire:target
    'ariaLabel' => null,
    'iconClass' => '',
])

@php
    $customClass = $attributes->get('class', '');
    $isFullWidth = str_contains($customClass, 'w-full');
    $widthClass = $isFullWidth ? 'flex w-full ' : 'flex w-full sm:inline-flex sm:w-auto ';

    $base = 'items-center justify-center transition-all duration-150 cursor-pointer whitespace-nowrap disabled:opacity-55 disabled:cursor-not-allowed disabled:shadow-none relative ';
    
    $fullBtn = $widthClass . $base . 'gap-1.5 px-4 min-h-[2.25rem] text-[length:var(--font-size-small)] rounded-md border tracking-wide ';
    $ghostBtn = $widthClass . $base . 'gap-1.5 px-3 min-h-[2.25rem] text-[length:var(--font-size-small)] rounded-md border border-transparent ';
    $linkBtn = 'inline-flex ' . $base . 'gap-1 min-h-[2.25rem] text-[length:var(--font-size-small)] border-none ';
    $iconBtn = 'group inline-flex ' . $base . 'rounded-lg w-9 h-9 p-2 border border-transparent active:scale-95 ';

    $baseClasses = match($variant) {
        // Solid
        'primary'       => $fullBtn . 'bg-primary-600 text-white font-semibold border-transparent hover:bg-primary-700 hover:ring-4 hover:ring-primary-600/10 active:bg-primary-800',
        'secondary'     => $fullBtn . 'bg-surface-card text-text-primary font-medium border-border hover:bg-surface-hover hover:border-border-strong active:bg-surface-hover/70',
        'success'       => $fullBtn . 'bg-success text-white font-semibold border-transparent hover:bg-success-hover hover:ring-4 hover:ring-success/10 active:bg-success-active',
        'danger'        => $fullBtn . 'bg-danger text-white font-semibold border-transparent hover:bg-danger-hover hover:ring-4 hover:ring-danger/10 active:bg-danger-active',
        'warning'       => $fullBtn . 'bg-warning text-white font-semibold border-transparent hover:bg-warning-hover hover:ring-4 hover:ring-warning/10 active:bg-warning-active',
        // Soft
        'soft'          => $fullBtn . 'bg-secondary-light text-text-secondary font-medium border-border/60 hover:bg-secondary-border hover:text-text-primary hover:border-border active:bg-border',
        'soft-primary'  => $fullBtn . 'bg-primary-50 text-primary-700 font-medium border-transparent hover:bg-primary-100 hover:text-primary-800 active:bg-primary-100/80',
        'soft-danger'   => $fullBtn . 'bg-danger-light text-danger font-medium border-transparent hover:bg-danger-light hover:text-danger-hover active:bg-danger-light/80',
        'soft-success'  => $fullBtn . 'bg-success-light text-success font-medium border-transparent hover:bg-success-light hover:text-success-hover active:bg-success-light/80',
        // Ghost
        'ghost'         => $ghostBtn . 'bg-transparent text-text-secondary font-medium hover:bg-surface-hover hover:text-text-primary active:bg-surface-main',
        'ghost-danger'  => $ghostBtn . 'bg-transparent text-text-muted font-medium hover:bg-danger-light hover:text-danger active:bg-danger-light/70',
        // Link
        'link'          => $linkBtn . 'bg-transparent text-primary-600 font-medium hover:text-primary-800 active:text-primary-900',
        'link-muted'    => $linkBtn . 'bg-transparent text-text-muted font-medium hover:text-text-primary',
        'link-danger'   => $linkBtn . 'bg-transparent text-danger font-medium hover:text-danger-hover',
        'link-danger-muted' => $linkBtn . 'bg-transparent text-text-muted font-medium hover:text-danger',
        // Icon
        'icon'          => $iconBtn . 'text-text-muted bg-transparent hover:bg-surface-hover hover:text-text-primary',
        'icon-primary'  => $iconBtn . 'text-text-muted bg-transparent hover:bg-primary-50 hover:text-primary-600',
        'icon-danger'   => $iconBtn . 'text-text-muted bg-transparent hover:bg-danger-light hover:text-danger',
        'icon-success'  => $iconBtn . 'text-text-muted bg-transparent hover:bg-success-light hover:text-success',
        'icon-warning'  => $iconBtn . 'text-text-muted bg-transparent hover:bg-warning-light hover:text-warning',
        'icon-secondary'=> $iconBtn . 'bg-surface-card text-text-secondary border-border hover:bg-surface-hover hover:border-border-strong hover:text-text-primary',
        default         => $fullBtn . 'bg-primary-600 text-white font-semibold border-transparent hover:bg-primary-700 hover:ring-4 hover:ring-primary-600/10 active:bg-primary-800',
    };

    $isIconButton = str_starts_with($variant, 'icon');
    $isLinkVariant = str_starts_with($variant, 'link');
    $baseIconClass = $isIconButton ? 'w-5 h-5 transition-transform duration-200 group-hover:scale-110' : 'w-4 h-4 shrink-0';
    $computedIconClass = $baseIconClass . ($iconClass ? ' ' . $iconClass : '');
    $computedAriaLabel = $ariaLabel ?? $attributes->get('aria-label') ?? $attributes->get('title') ?? ($isIconButton && $slot->isEmpty() ? 'Icon button' : null);
@endphp


@if($href)
    <a href="{!! $href !!}" {{ $attributes->merge(['class' => $baseClasses]) }} @if($computedAriaLabel) aria-label="{{ $computedAriaLabel }}" @endif>
        @if($icon)
            <x-dynamic-component :component="'lucide-' . $icon" class="{{ $computedIconClass }}" />
        @endif
        @if(!$isIconButton || $slot->isNotEmpty())
            {{ $slot }}
        @endif
        @if($iconRight)
            <x-dynamic-component :component="'lucide-' . $iconRight" class="{{ $computedIconClass }}" />
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
            <x-dynamic-component :component="'lucide-' . $icon" class="{{ $computedIconClass }}" />
        @endif

        @if(!$isIconButton || $slot->isNotEmpty())
            {{ $slot }}
        @endif

        @if($iconRight)
            <x-dynamic-component :component="'lucide-' . $iconRight" class="{{ $computedIconClass }}" />
        @endif

        @if($target)
            </span>
            <span wire:loading wire:target="{{ $target }}" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center justify-center">
                <span class="spinner spinner-sm"></span>
            </span>
        @endif
    </button>
@endif
