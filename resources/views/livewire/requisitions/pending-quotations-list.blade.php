<div wire:poll.10s>
    @if($pendingQuotations->isNotEmpty())
        <div class="space-y-3">
            @foreach($pendingQuotations as $pq)
                <div wire:key="pending-quotation-{{ $pq->id }}" class="flex items-center justify-between p-4 rounded-xl border bg-surface-card border-border hover:border-primary-300 transition-colors shadow-sm">
                    <div class="flex items-center gap-3">
                        @if($pq->isProcessing() || $pq->status === 'pending')
                            <div class="w-10 h-10 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 shadow-sm">
                                <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                            </div>
                            <div>
                                <p class="text-small font-semibold text-text-primary">Procesando cotización en segundo plano</p>
                                <p class="text-xs-fluid text-text-muted">{{ $pq->original_filename }} &bull; {{ $pq->created_at->diffForHumans() }}</p>
                            </div>
                        @elseif($pq->isCompleted())
                            <div class="w-10 h-10 rounded-full bg-success-light text-success flex items-center justify-center shrink-0 shadow-sm">
                                <i data-lucide="file-edit" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-small font-semibold text-text-primary">Borrador de Requisición listo</p>
                                <p class="text-xs-fluid text-text-muted">Procesado de: {{ $pq->original_filename }} &bull; {{ $pq->created_at->diffForHumans() }}</p>
                            </div>
                        @else
                            <div class="w-10 h-10 rounded-full bg-danger-light text-danger flex items-center justify-center shrink-0 shadow-sm">
                                <i data-lucide="file-x" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-small font-semibold text-text-primary">Error al extraer datos</p>
                                <p class="text-xs-fluid text-text-muted">{{ $pq->original_filename }} &bull; {{ $pq->created_at->diffForHumans() }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <x-button wire:click="dismissQuotation({{ $pq->id }})" wire:confirm="¿Descartar este borrador permanentemente?" variant="icon" icon="trash-2" class="text-text-muted hover:text-danger hover:bg-danger-50 transition-colors" title="Descartar" />
                        <x-button href="{{ route('requisiciones.upload', ['id' => $pq->id]) }}" variant="secondary" class="text-small" wire:navigate>
                            {{ $pq->isProcessing() || $pq->status === 'pending' ? 'Ver progreso' : 'Revisar y Continuar' }}
                            <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                        </x-button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <x-empty-state icon="check-square" title="No hay borradores pendientes"
            message="Todas tus cotizaciones han sido procesadas o enviadas a aprobación." />
    @endif
</div>
