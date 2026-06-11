@props(['align' => 'right', 'width' => '48', 'contentClasses' => ''])

@php
switch ($align) {
    case 'left':
        $anchorPosition = 'bottom-start';
        break;
    case 'top':
        $anchorPosition = 'top';
        break;
    case 'right':
    default:
        $anchorPosition = 'bottom-end';
        break;
}

switch ($width) {
    case '48':
        $widthClasses = 'w-48';
        break;
    case '56':
        $widthClasses = 'w-56';
        break;
    case '64':
        $widthClasses = 'w-64';
        break;
    default:
        $widthClasses = $width;
        break;
}
@endphp

<div class="relative"
     x-data="{
        open: false,
        id: $id('dropdown-menu')
     }"
     x-id="['dropdown-menu']"
     @close.stop="open = false"
     @dropdown-opened.window="if ($event.detail.id !== id) open = false">

    <div x-ref="trigger" @click="open = !open; if (open) $dispatch('dropdown-opened', { id })" class="cursor-pointer inline-flex items-center">
        {{ $trigger }}
    </div>

    <template x-teleport="body">
        <div x-show="open"
                @click.outside="if (! $refs.trigger.contains($event.target)) open = false"
                x-anchor.{{ $anchorPosition }}.offset.4="$refs.trigger"
                x-transition:enter="transition-premium"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition-premium"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute z-[100] {{ $widthClasses }} rounded-xl shadow-xl"
                style="display: none;">
            <div class="rounded-xl border border-border bg-surface-card overflow-hidden {{ $contentClasses }}"
                 @click="open = false">
                {{ $content }}
            </div>
        </div>
    </template>
</div>
