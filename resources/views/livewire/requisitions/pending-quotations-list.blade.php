<div wire:poll.10s.visible>
    @if($pendingQuotations->isNotEmpty())
        <div class="space-y-3">
            @foreach($pendingQuotations as $pq)
                <x-card wire:key="pending-quotation-{{ $pq->id }}" 
                    class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4 p-4 sm:p-6 transition-colors duration-200">

                    <div class="flex items-start sm:items-center gap-3.5 w-full sm:w-auto min-w-0 flex-1">
                        @if($pq->isProcessing() || $pq->status === 'pending')
                            {{-- Estado: procesando --}}
                            <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 shadow-sm">
                                <span class="spinner-processing !w-5 !h-5 !border-2"></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                @php
                                    $rawName = (string) ($pq->original_filename ?? 'documento');
                                    $info = pathinfo($rawName);
                                    $fileNameOnly = $info['filename'] ?? $rawName;
                                    $fileExt = isset($info['extension']) ? '.' . $info['extension'] : '';
                                @endphp
                                <div class="flex items-center gap-2 mb-0.5">
                                    <p class="text-body font-semibold text-text-primary">Procesando...</p>
                                    <x-badge variant="primary" size="sm">En curso</x-badge>
                                </div>
                                <div class="flex items-center gap-2 text-small text-text-muted mt-1 min-w-0 max-w-lg">
                                    <span class="inline-flex items-center min-w-0 font-medium text-text-secondary" title="{{ $rawName }}">
                                        <span class="truncate">{{ $fileNameOnly }}</span><span class="shrink-0">{{ $fileExt }}</span>
                                    </span>
                                    @if($pq->created_at)
                                        <span class="inline-flex items-center gap-1 text-xs-fluid font-medium text-text-muted shrink-0">
                                            <x-lucide-clock class="w-3.5 h-3.5 text-text-muted/70 shrink-0" wire:ignore />
                                            <span>{{ $pq->created_at->locale('es')->diffForHumans() }}</span>
                                        </span>
                                    @endif
                                </div>
                            </div>

                        @elseif($pq->isCompleted())
                            {{-- Estado: completado --}}
                            <div class="w-10 h-10 rounded-xl bg-success-light text-success flex items-center justify-center shrink-0 shadow-sm">
                                <x-lucide-file-edit class="w-5 h-5" wire:ignore />
                            </div>
                            <div class="min-w-0 flex-1">
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

                                    $title = $supplierName ? str($supplierName)->limit(28) : "Borrador listo";

                                    $rawName = (string) ($pq->original_filename ?? 'documento');
                                    $info = pathinfo($rawName);
                                    $fileNameOnly = $info['filename'] ?? $rawName;
                                    $fileExt = isset($info['extension']) ? '.' . $info['extension'] : '';
                                @endphp
                                <div class="flex items-center gap-2 mb-0.5">
                                    <p class="text-body font-semibold text-text-primary">{{ $title }}</p>
                                    @if($total)
                                        <x-badge variant="secondary" size="sm" class="tabular-nums font-bold">
                                            ${{ number_format((float)$total, 2) }}
                                        </x-badge>
                                    @else
                                        <x-badge variant="success" size="sm">Listo</x-badge>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-small text-text-muted mt-1 min-w-0 max-w-lg">
                                    <span class="inline-flex items-center min-w-0 font-medium text-text-secondary" title="{{ $rawName }}">
                                        <span class="truncate">{{ $fileNameOnly }}</span><span class="shrink-0">{{ $fileExt }}</span>
                                    </span>
                                    @if($pq->created_at)
                                        <span class="inline-flex items-center gap-1 text-xs-fluid font-medium text-text-muted shrink-0">
                                            <x-lucide-clock class="w-3.5 h-3.5 text-text-muted/70 shrink-0" wire:ignore />
                                            <span>{{ $pq->created_at->locale('es')->diffForHumans() }}</span>
                                        </span>
                                    @endif
                                </div>
                            </div>

                        @else
                            {{-- Estado: error --}}
                            <div class="w-10 h-10 rounded-xl bg-danger-light text-danger flex items-center justify-center shrink-0 shadow-sm">
                                <x-lucide-file-x class="w-5 h-5" wire:ignore />
                            </div>
                            <div class="min-w-0 flex-1">
                                @php
                                    $errLower = strtolower($pq->error_message ?? '');
                                    $isRateLimit = str_contains($errLower, 'saturado') || str_contains($errLower, 'demanda') || str_contains($errLower, 'cuota') || str_contains($errLower, 'reintenta');
                                    $badgeIcon = $isRateLimit ? 'clock' : 'alert-circle';
                                    $badgeText = $isRateLimit ? 'Saturado' : 'Manual';

                                    $rawName = (string) ($pq->original_filename ?? 'documento');
                                    $info = pathinfo($rawName);
                                    $fileNameOnly = $info['filename'] ?? $rawName;
                                    $fileExt = isset($info['extension']) ? '.' . $info['extension'] : '';
                                @endphp
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="text-body font-semibold text-text-primary">No procesado</p>
                                    <x-badge variant="danger" size="sm" :icon="$badgeIcon" title="{{ $pq->error_message }}">
                                        {{ $badgeText }}
                                    </x-badge>
                                </div>
                                @if($pq->error_message)
                                    <p class="text-small text-text-secondary font-medium mt-0.5 truncate max-w-[260px] sm:max-w-sm" title="{{ $pq->error_message }}">
                                        {{ Str::limit($pq->error_message, 45) }}
                                    </p>
                                @endif
                                <div class="flex items-center gap-2 text-small text-text-muted mt-1 min-w-0 max-w-lg">
                                    <span class="inline-flex items-center min-w-0 font-medium text-text-secondary" title="{{ $rawName }}">
                                        <span class="truncate">{{ $fileNameOnly }}</span><span class="shrink-0">{{ $fileExt }}</span>
                                    </span>
                                    @if($pq->created_at)
                                        <span class="inline-flex items-center gap-1 text-xs-fluid font-medium text-text-muted shrink-0">
                                            <x-lucide-clock class="w-3.5 h-3.5 text-text-muted/70 shrink-0" wire:ignore />
                                            <span>{{ $pq->created_at->locale('es')->diffForHumans() }}</span>
                                        </span>
                                    @endif
                                </div>
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
                        />

                        <x-button
                            href="{{ route('requisiciones.upload', ['ids' => [$pq->id], 'source' => 'borradores']) }}"
                            variant="secondary"
                            :iconRight="'arrow-right'"
                            wire:navigate
                        >
                            {{ $pq->isProcessing() || $pq->status === 'pending' ? 'Ver progreso' : 'Revisar' }}
                        </x-button>
                    </div>
                </x-card>
            @endforeach
        </div>
    @else
        <x-card class="p-4 sm:p-6">
            <x-empty-state
                icon="check-square"
                title="No hay borradores pendientes"
                message="Todas tus cotizaciones han sido procesadas o enviadas a aprobación." />
        </x-card>
    @endif

</div>
