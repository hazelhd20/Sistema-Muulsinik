<div wire:poll.10s.visible>
    @if($pendingQuotations->isNotEmpty())
        <div class="space-y-3">
            @foreach($pendingQuotations as $pq)
                <div wire:key="pending-quotation-{{ $pq->id }}"
                     class="flex items-center justify-between p-4 rounded-xl border bg-surface-card border-border hover:bg-surface-hover transition-colors shadow-sm">

                    <div class="flex items-center gap-3">
                        @if($pq->isProcessing() || $pq->status === 'pending')
                            {{-- Estado: procesando --}}
                            <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 shadow-sm">
                                <span class="spinner-processing !w-5 !h-5 !border-2"></span>
                            </div>
                            <div>
                                <p class="text-small font-semibold text-text-primary">Procesando cotización en segundo plano</p>
                                <p class="text-xs-fluid text-text-muted">
                                    {{ $pq->original_filename }} &bull; {{ $pq->created_at->locale('es')->diffForHumans() }}
                                </p>
                            </div>

                        @elseif($pq->isCompleted())
                            {{-- Estado: completado --}}
                            <div class="w-10 h-10 rounded-xl bg-success-light text-success flex items-center justify-center shrink-0 shadow-sm">
                                <i data-lucide="file-edit" class="w-5 h-5" wire:ignore></i>
                            </div>
                            <div>
                                <p class="text-small font-semibold text-text-primary">Borrador de Requisición listo</p>
                                <p class="text-xs-fluid text-text-muted">
                                    Procesado de: {{ $pq->original_filename }} &bull; {{ $pq->created_at->locale('es')->diffForHumans() }}
                                </p>
                            </div>

                        @else
                            {{-- Estado: error --}}
                            <div class="w-10 h-10 rounded-xl bg-danger-light text-danger flex items-center justify-center shrink-0 shadow-sm">
                                <i data-lucide="file-x" class="w-5 h-5" wire:ignore></i>
                            </div>
                            <div>
                                <p class="text-small font-semibold text-text-primary">Error al extraer datos</p>
                                <p class="text-xs-fluid text-text-muted">
                                    {{ $pq->original_filename }} &bull; {{ $pq->created_at->locale('es')->diffForHumans() }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        {{-- Descartar con confirm-modal (SVG Nativo en slot para prevenir parpadeo) --}}
                        <x-button
                            @click="$dispatch('confirm-action', {
                                title: 'Descartar Borrador',
                                description: '{{ addslashes($pq->original_filename) }} será descartado permanentemente.',
                                confirmLabel: 'Descartar',
                                variant: 'danger',
                                action: 'dismissQuotation',
                                params: [{{ $pq->id }}]
                            })"
                            variant="icon-danger"
                            icon="trash-2"
                            title="Descartar borrador"
                            aria-label="Descartar {{ $pq->original_filename }}"
                        />

                        <x-button
                            href="{{ route('requisiciones.upload', ['id' => $pq->id]) }}"
                            variant="secondary"
                            :iconRight="'arrow-right'"
                            wire:navigate
                        >
                            {{ $pq->isProcessing() || $pq->status === 'pending' ? 'Ver progreso' : 'Revisar y Continuar' }}
                        </x-button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <x-empty-state
            icon="check-square"
            title="No hay borradores pendientes"
            message="Todas tus cotizaciones han sido procesadas o enviadas a aprobación." />
    @endif

    {{-- Diálogo de confirmación (para descartar borradores) --}}
    <x-confirm-modal />
</div>
