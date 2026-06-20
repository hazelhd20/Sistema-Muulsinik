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
    $baseClasses = match($variant) {
        'primary'       => 'btn-primary relative',
        'secondary'     => 'btn-secondary relative',
        'success'       => 'btn-success relative',
        'danger'        => 'btn-danger relative',
        'warning'       => 'btn-warning relative',
        // Soft — fondo suave sin peso de secondary. Ideal en cards/wizards.
        'soft'          => 'btn-soft relative',
        'soft-primary'  => 'btn-soft-primary relative',
        'soft-danger'   => 'btn-soft-danger relative',
        // Ghost — sin fondo en reposo, aparece al hover. Para acciones de bajo peso.
        'ghost'         => 'btn-ghost relative',
        'ghost-danger'  => 'btn-ghost-danger relative',
        // Link — solo texto. Peso mínimo. Para "Cancelar", "Limpiar", "Ver más".
        'link'          => 'btn-link',
        'link-muted'    => 'btn-link-muted',
        'link-danger'   => 'btn-link-danger',
        'link-danger-muted' => 'btn-link-danger-muted',
        // Icon-only
        'icon'          => 'btn-icon relative',
        'icon-primary'  => 'btn-icon-primary relative',
        'icon-secondary'=> 'btn-icon-secondary relative',
        'icon-danger'   => 'btn-icon-danger relative',
        'icon-success'  => 'btn-icon-success relative',
        'icon-warning'  => 'btn-icon-warning relative',
        default         => 'btn-primary relative',
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
