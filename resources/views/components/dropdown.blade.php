@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1'])

@php
switch ($align) {
    case 'left':
        $alignmentClasses = 'origin-top-left left-0';
        break;
    case 'top':
        $alignmentClasses = 'origin-top';
        break;
    case 'right':
    default:
        $alignmentClasses = 'origin-top-right right-0';
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
     @click.outside="open = false" 
     @close.stop="open = false"
     @dropdown-opened.window="if ($event.detail.id !== id) open = false">
    <div @click="open = !open; if (open) $dispatch('dropdown-opened', { id })" class="cursor-pointer inline-flex items-center">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-1"
            class="absolute z-50 mt-1 {{ $widthClasses }} rounded-xl shadow-lg {{ $alignmentClasses }}"
            style="display: none;">
        <div class="rounded-xl border border-border bg-surface-card overflow-hidden {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
