@props(['label', 'value' => null, 'align' => 'left'])

<div class="{{ $align === 'right' ? 'text-right' : '' }}">
    <p class="text-xs-fluid text-text-muted uppercase font-semibold tracking-wider mb-1">{{ $label }}</p>
    <div class="text-body font-medium text-text-primary">
        @if($value !== null)
            {{ $value }}
        @else
            {{ $slot->isEmpty() ? '—' : $slot }}
        @endif
    </div>
</div>
