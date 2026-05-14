@props(['subtitle' => null, 'title' => null])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        @if($subtitle)
            <p class="text-xs-fluid font-semibold text-text-muted uppercase tracking-widest mb-0.5">{{ $subtitle }}</p>
        @endif
        <h1 class="text-h1 text-text-primary">
            @if(isset($heading))
                {{ $heading }}
            @else
                {{ $title }}
            @endif
        </h1>
    </div>
    @if(isset($actions))
        <div class="flex items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
