@props(['activity'])

@php
    $actionConfig = match($activity->action) {
        'created' => ['icon' => 'plus-circle', 'color' => 'text-primary-600', 'bg' => 'bg-primary-50'],
        'approved' => ['icon' => 'check-circle', 'color' => 'text-success', 'bg' => 'bg-success-light'],
        'rejected' => ['icon' => 'x-circle', 'color' => 'text-danger', 'bg' => 'bg-danger-light'],
        'status_changed' => ['icon' => 'arrow-right-circle', 'color' => 'text-warning', 'bg' => 'bg-warning-light'],
        default => ['icon' => 'edit-3', 'color' => 'text-text-secondary', 'bg' => 'bg-surface-hover'],
    };
@endphp

<div class="relative flex items-start gap-4 md:gap-6 group">
    {{-- Line & Icon --}}
    <div class="relative z-10 flex items-center justify-center w-10 h-10 rounded-full shrink-0 ring-4 ring-surface-card {{ $actionConfig['bg'] }} transition-transform group-hover:scale-110">
        <x-dynamic-component :component="'lucide-' . $actionConfig['icon']" class="w-5 h-5 {{ $actionConfig['color'] }}" />
    </div>
    
    {{-- Content --}}
    <div class="flex-1 min-w-0 pt-1">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-1">
            <p class="text-small font-medium text-text-primary">
                {{ $activity->description ?? ucfirst(__($activity->action)) }}
            </p>
            <span class="text-xs text-text-muted whitespace-nowrap" title="{{ $activity->created_at->format('d/m/Y H:i:s') }}">
                {{ $activity->created_at->diffForHumans() }}
            </span>
        </div>
        <div class="text-xs text-text-muted flex items-center gap-1.5">
            <x-lucide-user class="w-3.5 h-3.5" />
            {{ $activity->user ? $activity->user->name : 'Sistema' }}
        </div>

        @if($activity->old_values || $activity->new_values)
            <div class="mt-3 p-4 rounded-lg bg-surface-main/30 border border-border overflow-x-auto shadow-sm">
                @if(in_array($activity->action, ['status_changed', 'approved', 'rejected']) && (isset($activity->old_values['status']) || isset($activity->new_values['status'])))
                    <div class="flex items-center gap-3 text-small font-medium">
                        <span class="text-text-muted line-through">{{ strtoupper($activity->old_values['status'] ?? '—') }}</span>
                        <x-lucide-arrow-right class="w-4 h-4 text-text-secondary" />
                        <span class="{{ $actionConfig['color'] }}">{{ strtoupper($activity->new_values['status'] ?? '—') }}</span>
                    </div>
                @else
                    <pre class="text-xs text-text-secondary font-mono leading-relaxed">{{ json_encode(['De' => $activity->old_values, 'A' => $activity->new_values], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
            </div>
        @endif
    </div>
</div>
