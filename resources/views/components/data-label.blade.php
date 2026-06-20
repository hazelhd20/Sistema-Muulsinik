@props(['label', 'value' => null])

<div>
    <span class="text-xs text-text-muted block mb-1">{{ $label }}</span>
    <span class="text-small font-medium text-text-primary">
        @if($value !== null)
            {{ $value }}
        @else
            {{ $slot->isEmpty() ? '—' : $slot }}
        @endif
    </span>
</div>
