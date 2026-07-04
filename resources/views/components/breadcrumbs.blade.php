@props(['links' => []])

@php
    $cleanedLinks = collect($links)->reject(function ($link) {
        $label = is_array($link) ? ($link['label'] ?? '') : $link;
        $url   = is_array($link) ? ($link['url'] ?? '') : '';
        return strtolower(trim($label)) === 'inicio' || $url === url('/') || $url === url('/dashboard');
    })->values()->all();
@endphp

@if(!empty($cleanedLinks))
    <nav class="flex text-small text-text-secondary font-medium select-none items-center my-auto" aria-label="Breadcrumb">
        <ol class="inline-flex items-center flex-nowrap sm:flex-wrap gap-y-1 overflow-x-auto no-scrollbar max-w-full">
            @foreach($cleanedLinks as $index => $link)
                <li class="inline-flex items-center shrink-0">
                    @if(isset($link['url']) && !$loop->last)
                        <a href="{!! $link['url'] !!}" wire:navigate class="inline-flex items-center text-text-secondary hover:text-primary-600 transition-colors truncate max-w-[120px] sm:max-w-none">
                            @if(isset($link['icon']))
                                <x-dynamic-component :component="'lucide-' . $link['icon']" class="w-4 h-4 mr-1.5 shrink-0 opacity-80" />
                            @endif
                            <span class="truncate">{{ $link['label'] ?? $link }}</span>
                        </a>
                        <x-lucide-chevron-right class="w-4 h-4 mx-1.5 sm:mx-2 text-border-strong shrink-0 opacity-60" />
                    @else
                        <span class="inline-flex items-center text-text-primary font-semibold truncate max-w-[140px] sm:max-w-[300px]" aria-current="page" title="{{ $link['label'] ?? $link }}">
                            @if(isset($link['icon']))
                                <x-dynamic-component :component="'lucide-' . $link['icon']" class="w-4 h-4 mr-1.5 shrink-0 text-primary-600" />
                            @endif
                            <span class="truncate">{{ $link['label'] ?? $link }}</span>
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
