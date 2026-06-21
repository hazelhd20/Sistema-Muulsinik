@props(['title' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'px-6 pt-6 pb-4 flex flex-col sm:flex-row sm:items-start justify-between gap-4']) }}>
    @if($title || $subtitle)
        <div>
            @if($title)
                <h2 class="card-title">{{ $title }}</h2>
            @endif
            
            @if($subtitle)
                <p class="text-xs text-text-muted mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    
    @if(isset($action))
        <div class="flex-shrink-0">
            {{ $action }}
        </div>
    @endif

    {{ $slot }}
</div>
