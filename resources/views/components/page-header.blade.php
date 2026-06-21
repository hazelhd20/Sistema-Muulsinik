@props(['subtitle' => null, 'title' => null, 'description' => null, 'backUrl' => null, 'breadcrumbs' => [], 'icon' => null, 'status' => null])

<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6 pb-5 border-b border-border/40">
    <div class="flex items-start gap-4">
        @if($icon)
            <div class="hidden sm:flex mt-1 items-center justify-center w-10 h-10 rounded-lg bg-surface-card border border-border/60 shadow-sm shrink-0">
                <x-dynamic-component :component="'lucide-' . $icon" class="w-5 h-5 text-text-muted" />
            </div>
        @endif
        
        <div class="flex flex-col min-w-0">
            @if(isset($breadcrumbs) && count($breadcrumbs) > 0)
                <x-breadcrumbs :links="$breadcrumbs" />
            @elseif($subtitle)
                <div class="flex items-center gap-3 mb-1">
                    @if($backUrl)
                        <a href="{!! $backUrl !!}" wire:navigate class="btn-icon text-text-muted hover:text-text-primary shrink-0 transition-colors -ml-1.5">
                            <x-lucide-arrow-left class="w-4 h-4" />
                        </a>
                    @endif
                    <p class="text-xs font-semibold text-text-muted uppercase tracking-widest truncate">{{ $subtitle }}</p>
                </div>
            @endif
            
            <div class="flex items-center gap-3 mt-0.5 flex-wrap">
                @if(!$subtitle && count($breadcrumbs) === 0 && $backUrl)
                    <a href="{!! $backUrl !!}" wire:navigate class="btn-icon text-text-muted hover:text-text-primary shrink-0 transition-colors -ml-1.5">
                        <x-lucide-arrow-left class="w-5 h-5" />
                    </a>
                @endif
                <h1 class="text-h1 text-text-primary truncate">
                    @if(isset($heading))
                        {{ $heading }}
                    @else
                        {{ $title }}
                    @endif
                </h1>
                
                @if($status)
                    <x-status-badge
                        :status="$status"
                        :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
                @endif
            </div>

            @if($description || isset($descriptionSlot))
                <div class="mt-1.5 text-small text-text-muted">
                    {{ $description ?? $descriptionSlot }}
                </div>
            @endif
        </div>
    </div>
    
    @if(isset($actions))
        <div class="flex flex-wrap items-center gap-2 mt-2 sm:mt-0 shrink-0">
            {{ $actions }}
        </div>
    @endif
</div>
