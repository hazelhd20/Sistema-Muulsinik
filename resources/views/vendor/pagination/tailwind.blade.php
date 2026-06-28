@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between">
        
        {{-- Mobile View --}}
        <div class="flex items-center justify-between flex-1 sm:hidden gap-2">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center justify-center px-3 h-8 text-xs font-medium text-text-muted bg-surface-card border border-border cursor-default pointer-events-none rounded-md opacity-40 gap-1">
                    <x-lucide-chevron-left class="w-3.5 h-3.5" />
                    <span>{!! __('pagination.previous') !!}</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center justify-center px-3 h-8 text-xs font-medium text-text-primary bg-surface-card border border-border rounded-md hover:bg-surface-hover hover:border-border-strong transition-all duration-150 gap-1">
                    <x-lucide-chevron-left class="w-3.5 h-3.5" />
                    <span>{!! __('pagination.previous') !!}</span>
                </a>
            @endif

            <span class="text-xs text-text-muted font-medium bg-surface-card px-2.5 py-1 border border-border rounded-md">
                Pág. {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center justify-center px-3 h-8 text-xs font-medium text-text-primary bg-surface-card border border-border rounded-md hover:bg-surface-hover hover:border-border-strong transition-all duration-150 gap-1">
                    <span>{!! __('pagination.next') !!}</span>
                    <x-lucide-chevron-right class="w-3.5 h-3.5" />
                </a>
            @else
                <span class="relative inline-flex items-center justify-center px-3 h-8 text-xs font-medium text-text-muted bg-surface-card border border-border cursor-default pointer-events-none rounded-md opacity-40 gap-1">
                    <span>{!! __('pagination.next') !!}</span>
                    <x-lucide-chevron-right class="w-3.5 h-3.5" />
                </span>
            @endif
        </div>

        {{-- Desktop View --}}
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-text-muted leading-5">
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="font-semibold text-text-primary">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-semibold text-text-primary">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('of') !!}
                    <span class="font-semibold text-text-primary">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex items-center gap-1.5">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="relative inline-flex items-center justify-center px-2.5 h-8 text-xs font-medium text-text-muted bg-surface-card border border-border cursor-default pointer-events-none rounded-md opacity-40 gap-1" aria-hidden="true">
                                <x-lucide-chevron-left class="w-3.5 h-3.5" />
                                <span>{!! __('pagination.previous') !!}</span>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center justify-center px-2.5 h-8 text-xs font-medium text-text-primary bg-surface-card border border-border rounded-md hover:bg-surface-hover hover:border-border-strong transition-all duration-150 gap-1" aria-label="{{ __('pagination.previous') }}">
                            <x-lucide-chevron-left class="w-3.5 h-3.5" />
                            <span>{!! __('pagination.previous') !!}</span>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    <div class="flex items-center gap-1">
                        @foreach ($elements as $element)
                            {{-- "Three Dots" Separator --}}
                            @if (is_string($element))
                                <span aria-disabled="true">
                                    <span class="relative inline-flex items-center justify-center w-8 h-8 text-xs font-medium text-text-muted cursor-default">{{ $element }}</span>
                                </span>
                            @endif

                            {{-- Array Of Links --}}
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    @if ($page == $paginator->currentPage())
                                        <span aria-current="page">
                                            <span class="relative inline-flex items-center justify-center w-8 h-8 text-xs font-semibold text-primary-700 bg-primary-50 border border-primary-200/60 rounded-md">{{ $page }}</span>
                                        </span>
                                    @else
                                        <a href="{{ $url }}" class="relative inline-flex items-center justify-center w-8 h-8 text-xs font-medium text-text-secondary rounded-md hover:bg-surface-hover hover:text-text-primary transition-all duration-150" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                            {{ $page }}
                                        </a>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    </div>

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center justify-center px-2.5 h-8 text-xs font-medium text-text-primary bg-surface-card border border-border rounded-md hover:bg-surface-hover hover:border-border-strong transition-all duration-150 gap-1" aria-label="{{ __('pagination.next') }}">
                            <span>{!! __('pagination.next') !!}</span>
                            <x-lucide-chevron-right class="w-3.5 h-3.5" />
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="relative inline-flex items-center justify-center px-2.5 h-8 text-xs font-medium text-text-muted bg-surface-card border border-border cursor-default pointer-events-none rounded-md opacity-40 gap-1" aria-hidden="true">
                                <span>{!! __('pagination.next') !!}</span>
                                <x-lucide-chevron-right class="w-3.5 h-3.5" />
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
