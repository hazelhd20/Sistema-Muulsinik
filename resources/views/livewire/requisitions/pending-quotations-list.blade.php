<div wire:poll.10s.visible>
    @if($pendingQuotations->isNotEmpty())
        <div class="space-y-3">
            @foreach($pendingQuotations as $pq)
                <x-card wire:key="pending-quotation-{{ $pq->id }}" 
                    class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4 p-4 hover:shadow-md transition-shadow duration-200">

                    <div class="flex items-start sm:items-center gap-3.5 w-full sm:w-auto">
                        @if($pq->isProcessing() || $pq->status === 'pending')
                            {{-- Estado: procesando --}}
                            <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 shadow-sm">
                                <span class="spinner-processing !w-5 !h-5 !border-2"></span>
                            </div>
                            <div class="min-w-0 flex-1 sm:flex-initial">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <p class="text-small font-semibold text-text-primary">Procesando cotización</p>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold tracking-wider uppercase bg-primary-50 text-primary-700">
                                        Analizando
                                    </span>
                                </div>
                                <p class="text-xs text-text-muted truncate max-w-md">
                                    {{ $pq->original_filename }} &bull; {{ $pq->created_at->locale('es')->diffForHumans() }}
                                </p>
                            </div>

                        @elseif($pq->isCompleted())
                            {{-- Estado: completado --}}
                            <div class="w-10 h-10 rounded-xl bg-success-light text-success flex items-center justify-center shrink-0 shadow-sm">
                                <x-lucide-file-edit class="w-5 h-5" wire:ignore />
                            </div>
                            <div class="min-w-0 flex-1 sm:flex-initial">
                                @php
                                    $supplierName = !empty($pq->draft_state['supplierName']) 
                                        ? $pq->draft_state['supplierName'] 
                                        : ($pq->raw_parsed_data['supplier'] ?? null);

                                    $total = null;
                                    if (!empty($pq->draft_state['items'])) {
                                        $total = collect($pq->draft_state['items'])->sum(fn($item) => (float)($item['line_total'] ?? 0));
                                    } else {
                                        $total = $pq->raw_parsed_data['tax_info']['grand_total'] ?? null;
                                    }

                                    $title = $supplierName ? "Borrador: {$supplierName}" : "Borrador de Requisición listo";
                                @endphp
                                <div class="flex items-center gap-2 mb-0.5">
                                    <p class="text-small font-semibold text-text-primary">{{ $title }}</p>
                                    @if($total)
                                        <span class="text-[11px] font-semibold text-text-primary bg-surface-main px-2 py-0.5 rounded-md tabular-nums">
                                            ${{ number_format((float)$total, 2) }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-text-muted truncate max-w-md">
                                    Procesado de: {{ $pq->original_filename }} &bull; {{ $pq->created_at->locale('es')->diffForHumans() }}
                                </p>
                            </div>

                        @else
                            {{-- Estado: error --}}
                            <div class="w-10 h-10 rounded-xl bg-danger-light text-danger flex items-center justify-center shrink-0 shadow-sm">
                                <x-lucide-file-x class="w-5 h-5" wire:ignore />
                            </div>
                            <div class="min-w-0 flex-1 sm:flex-initial">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <p class="text-small font-semibold text-text-primary">Error de extracción</p>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold tracking-wider uppercase bg-danger-light text-danger">
                                        Falló
                                    </span>
                                </div>
                                @if($pq->error_message)
                                    <p class="text-xs text-danger font-medium mt-0.5 leading-relaxed max-w-md">
                                        {{ \Illuminate\Support\Str::limit($pq->error_message, 80) }}
                                    </p>
                                @endif
                                <p class="text-xs text-text-muted mt-0.5">
                                    Archivo: {{ $pq->original_filename }} &bull; {{ $pq->created_at->locale('es')->diffForHumans() }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end pt-2 sm:pt-0 border-t sm:border-0 border-border/50 sm:border-transparent mt-1 sm:mt-0">
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
                            class="opacity-60 hover:opacity-100 transition-opacity rounded-xl"
                        />

                        <x-button
                            href="{{ route('requisiciones.upload', ['ids' => [$pq->id], 'source' => 'borradores']) }}"
                            variant="secondary"
                            :iconRight="'arrow-right'"
                            wire:navigate
                            class="rounded-xl font-medium text-xs py-2"
                        >
                            {{ $pq->isProcessing() || $pq->status === 'pending' ? 'Ver progreso' : 'Revisar' }}
                        </x-button>
                    </div>
                </x-card>
            @endforeach
        </div>
    @else
        <x-card class="p-8">
            <x-empty-state
                icon="check-square"
                title="No hay borradores pendientes"
                message="Todas tus cotizaciones han sido procesadas o enviadas a aprobación." />
        </x-card>
    @endif

</div>
