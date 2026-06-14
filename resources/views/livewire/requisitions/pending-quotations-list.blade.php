<div wire:poll.10s.visible>
    @if($pendingQuotations->isNotEmpty())
        <div class="space-y-3">
            @foreach($pendingQuotations as $pq)
                <div wire:key="pending-quotation-{{ $pq->id }}"
                     class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4 p-4 rounded-xl border bg-surface-card border-border hover:bg-surface-hover transition-colors shadow-sm">

                    <div class="flex items-start sm:items-center gap-3 w-full sm:w-auto">
                        @if($pq->isProcessing() || $pq->status === 'pending')
                            {{-- Estado: procesando --}}
                            <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 shadow-sm">
                                <span class="spinner-processing !w-5 !h-5 !border-2"></span>
                            </div>
                            <div>
                                <p class="text-small font-semibold text-text-primary">Procesando cotización en segundo plano</p>
                                <p class="text-xs text-text-muted">
                                    {{ $pq->original_filename }} &bull; {{ $pq->created_at->locale('es')->diffForHumans() }}
                                </p>
                            </div>

                        @elseif($pq->isCompleted())
                            {{-- Estado: completado --}}
                            <div class="w-10 h-10 rounded-xl bg-success-light text-success flex items-center justify-center shrink-0 shadow-sm">
                                <x-lucide-file-edit class="w-5 h-5" wire:ignore />
                            </div>
                            <div>
                                @php
                                    $supplierName = $pq->supplier?->name ?? ($pq->raw_parsed_data['supplier_name'] ?? null);
                                    $total = $pq->draft_state['total'] ?? ($pq->raw_parsed_data['total'] ?? null);
                                    $title = $supplierName ? "Borrador: {$supplierName}" : "Borrador de Requisición listo";
                                @endphp
                                <div class="flex items-center gap-2 mb-0.5">
                                    <p class="text-small font-semibold text-text-primary">{{ $title }}</p>
                                    @if($total)
                                        <span class="text-[0.65rem] font-medium text-success bg-success/10 px-1.5 py-0.5 rounded-md border border-success/20">
                                            ${{ number_format((float)$total, 2) }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-text-muted">
                                    Procesado de: {{ $pq->original_filename }} &bull; {{ $pq->created_at->locale('es')->diffForHumans() }}
                                </p>
                            </div>

                        @else
                            {{-- Estado: error --}}
                            <div class="w-10 h-10 rounded-xl bg-danger-light text-danger flex items-center justify-center shrink-0 shadow-sm">
                                <x-lucide-file-x class="w-5 h-5" wire:ignore />
                            </div>
                            <div>
                                <p class="text-small font-semibold text-text-primary">Error al extraer datos</p>
                                <p class="text-xs text-text-muted">
                                    {{ $pq->original_filename }} &bull; {{ $pq->created_at->locale('es')->diffForHumans() }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end pt-2 sm:pt-0 border-t sm:border-0 border-border/50 sm:border-transparent mt-1 sm:mt-0">
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
                            href="{{ route('requisiciones.upload', ['ids' => [$pq->id], 'source' => 'borradores']) }}"
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
        <div class="bg-surface-card border border-border rounded-xl shadow-sm p-8">
            <x-empty-state
                icon="check-square"
                title="No hay borradores pendientes"
                message="Todas tus cotizaciones han sido procesadas o enviadas a aprobación." />
        </div>
    @endif

</div>
