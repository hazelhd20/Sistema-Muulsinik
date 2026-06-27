@props([
    'subtitle'    => null, 
    'title'       => null, 
    'description' => null, 
    'backUrl'     => null, 
    'breadcrumbs' => [], 
    'icon'        => null, 
    'status'      => null,
    'sticky'      => false,
])

<div @class([
    'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 pb-2 transition-all duration-150',
    'sticky top-0 z-30 bg-surface-main/85 backdrop-blur-md -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 pt-4 pb-4' => $sticky,
])>
    <div class="flex-1 min-w-0">
        {{-- Nivel Superior: Breadcrumbs teletransportados a Topbar (Desktop) y locales en Móvil --}}
        @if(isset($breadcrumbs) && count($breadcrumbs) > 0)
            @push('topbar-breadcrumbs')
                <x-breadcrumbs :links="$breadcrumbs" />
            @endpush

            <div class="lg:hidden mb-2">
                <x-breadcrumbs :links="$breadcrumbs" />
            </div>
        @elseif($subtitle || $backUrl)
            <div class="flex items-center gap-2 mb-1.5 text-xs font-semibold text-text-muted tracking-widest uppercase select-none">
                @if($backUrl)
                    <a href="{!! $backUrl !!}" wire:navigate class="btn-icon shrink-0 w-6 h-6 p-0 -ml-1 text-text-muted hover:text-text-primary transition-colors" title="Volver">
                        <x-lucide-arrow-left class="w-4 h-4" />
                    </a>
                @endif
                @if($subtitle)
                    <span class="truncate">{{ $subtitle }}</span>
                @endif
            </div>
        @endif
        
        {{-- Nivel Principal: H1 + Status Badge --}}
        <div class="flex items-center gap-3 flex-wrap">
            @if(!$subtitle && count($breadcrumbs) === 0 && $backUrl)
                <a href="{!! $backUrl !!}" wire:navigate class="btn-icon shrink-0 w-7 h-7 p-0 -ml-1 text-text-muted hover:text-text-primary transition-colors" title="Volver">
                    <x-lucide-arrow-left class="w-5 h-5" />
                </a>
            @endif

            <h1 class="text-h1 font-bold text-text-primary tracking-tight tabular-nums truncate">
                @if(isset($heading))
                    {{ $heading }}
                @else
                    {{ $title }}
                @endif
            </h1>
            
            @if($status)
                <x-status-badge
                    :status="$status"
                    size="md"
                    :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
            @endif
        </div>

        {{-- Nivel Inferior: Descripción o Metadatos --}}
        @if($description || isset($descriptionSlot))
            <div class="mt-1.5 text-small text-text-muted leading-relaxed">
                {{ $description ?? $descriptionSlot }}
            </div>
        @endif
    </div>
    
    {{-- Bloque Derecho: Acciones --}}
    @if(isset($actions))
        <div class="flex flex-wrap items-center gap-2 self-start sm:self-center shrink-0 pt-1 sm:pt-0">
            {{ $actions }}
        </div>
    @endif
</div>
