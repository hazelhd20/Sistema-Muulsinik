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
                                    {{ $pq->original_filename }} &bull; {{ $pq->created_at->diffForHumans() }}
                                </p>
                            </div>

                        @elseif($pq->isCompleted())
                            {{-- Estado: completado (SVG Nativo para 0% parpadeo) --}}
                            <div class="w-10 h-10 rounded-xl bg-success-light text-success flex items-center justify-center shrink-0 shadow-sm">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-small font-semibold text-text-primary">Borrador de Requisición listo</p>
                                <p class="text-xs-fluid text-text-muted">
                                    Procesado de: {{ $pq->original_filename }} &bull; {{ $pq->created_at->diffForHumans() }}
                                </p>
                            </div>

                        @else
                            {{-- Estado: error (SVG Nativo para 0% parpadeo) --}}
                            <div class="w-10 h-10 rounded-xl bg-danger-light text-danger flex items-center justify-center shrink-0 shadow-sm">
                                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="9" y1="15" x2="15" y2="9"></line>
                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                                </svg>
                            </div>
                            <div>
                                <p class="text-small font-semibold text-text-primary">Error al extraer datos</p>
                                <p class="text-xs-fluid text-text-muted">
                                    {{ $pq->original_filename }} &bull; {{ $pq->created_at->diffForHumans() }}
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
                            variant="icon"
                            class="text-text-muted hover:text-danger hover:bg-danger-light transition-colors"
                            title="Descartar borrador"
                            aria-label="Descartar {{ $pq->original_filename }}"
                        >
                            <svg class="w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                        </x-button>

                        <x-button
                            href="{{ route('requisiciones.upload', ['id' => $pq->id]) }}"
                            variant="secondary"
                            wire:navigate
                        >
                            <span class="inline-flex items-center gap-1.5">
                                <span>{{ $pq->isProcessing() || $pq->status === 'pending' ? 'Ver progreso' : 'Revisar y Continuar' }}</span>
                                <svg class="w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </span>
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
