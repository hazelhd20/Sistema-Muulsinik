@props(['subtitle' => null, 'title' => null, 'backUrl' => null])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div class="flex items-center gap-3">
        @if($backUrl)
            <a href="{!! $backUrl !!}" wire:navigate class="btn-icon text-text-muted hover:text-text-primary shrink-0 transition-colors">
                <x-lucide-arrow-left class="w-5 h-5" />
            </a>
        @endif
        <div>
            @if($subtitle)
                <p class="text-xs font-semibold text-text-muted uppercase tracking-widest mb-0.5">{{ $subtitle }}</p>
            @endif
            <h1 class="text-h1 text-text-primary">
                @if(isset($heading))
                    {{ $heading }}
                @else
                    {{ $title }}
                @endif
            </h1>
        </div>
    </div>
    @if(isset($actions))
        <div class="flex items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
