@props(['links' => []])

@if(!empty($links))
    <nav class="flex text-xs text-text-muted mb-2 font-medium" aria-label="Breadcrumb">
        <ol class="inline-flex items-center flex-wrap gap-y-1">
            @foreach($links as $index => $link)
                <li class="inline-flex items-center">
                    @if(isset($link['url']) && !$loop->last)
                        <a href="{!! $link['url'] !!}" wire:navigate class="inline-flex items-center hover:text-text-primary transition-colors">
                            {{ $link['label'] }}
                        </a>
                        <x-lucide-chevron-right class="w-3.5 h-3.5 mx-1.5 opacity-50 shrink-0" />
                    @else
                        <span class="text-text-primary font-semibold truncate max-w-[200px] sm:max-w-[300px]" aria-current="page" title="{{ $link['label'] ?? $link }}">
                            {{ $link['label'] ?? $link }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
