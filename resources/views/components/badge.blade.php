@props([
    'variant' => 'secondary',
    'dot' => false,
    'icon' => null,
])

<span {{ $attributes->class(['badge', "badge-{$variant}"]) }}>
    @if($dot)<span class="badge-dot"></span>@endif
    @if($icon)<i data-lucide="{{ $icon }}" class="w-3 h-3 mr-1 inline-block"></i>@endif
    {{ $slot }}
</span>
