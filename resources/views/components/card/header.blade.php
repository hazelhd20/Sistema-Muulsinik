@props(['title' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'px-4 sm:px-6 py-3.5 sm:py-4 border-b border-border/40 flex items-center justify-between gap-3 sm:gap-4']) }}>
    @if($title || $subtitle)
        <div class="flex flex-col min-w-0">
            @if($title)
                <h3 class="font-medium text-text-primary tracking-tight truncate">{{ $title }}</h3>
            @endif
            
            @if($subtitle)
                <p class="text-xs text-text-muted mt-0.5 truncate">{{ $subtitle }}</p>
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
