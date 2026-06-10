<div>
    {{-- Drawer de Detalle Rápido --}}
    <x-drawer show="showDetailDrawer" title="Detalles de Requisición" maxWidth="xl">
        @if($detailRequisition)
            <div class="space-y-6">
                {{-- Resumen principal --}}
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-h3 font-semibold text-text-primary">
                            {{ $detailRequisition->number ?? 'REQ-' . str_pad($detailRequisition->id, 5, '0', STR_PAD_LEFT) }}
                        </h3>
                        <p class="text-small text-text-muted mt-1">
                            Creada el {{ $detailRequisition->date?->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
                        </p>
                    </div>
                    <x-status-badge
                        :status="$detailRequisition->status"
                        :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
                </div>

                {{-- Detalles en grid --}}
                <div class="grid grid-cols-2 gap-4 text-small bg-surface-main/30 p-4 rounded-xl border border-border">
                    <div>
                        <p class="text-text-muted text-xs-fluid font-medium mb-0.5">Proyecto</p>
                        <p class="font-medium text-text-primary">{{ $detailRequisition->project?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-text-muted text-xs-fluid font-medium mb-0.5">Solicitante</p>
                        <p class="font-medium text-text-primary">{{ $detailRequisition->creator?->name ?? '—' }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-text-muted text-xs-fluid font-medium mb-0.5">Proveedor Seleccionado</p>
                        <p class="font-medium text-text-primary">
                            {{ $detailRequisition->vendor?->trade_name ?? $detailRequisition->vendor?->name ?? '—' }}
                        </p>
                    </div>

                    @if($detailRequisition->approver && in_array($detailRequisition->status, ['aprobada', 'rechazada']))
                        <div class="col-span-2">
                            <p class="text-text-muted text-xs-fluid font-medium mb-0.5">
                                {{ $detailRequisition->status === 'aprobada' ? 'Aprobada por' : 'Rechazada por' }}
                            </p>
                            <p class="font-medium text-text-primary">{{ $detailRequisition->approver->name }}</p>
                        </div>
                    @endif
                </div>

                {{-- Banner de rechazo --}}
                @if($detailRequisition->status === 'rechazada' && $detailRequisition->rejection_comment)
                    <div class="flex items-start gap-3 p-4 rounded-xl bg-danger-light border border-danger-border">
                        <i data-lucide="x-octagon" class="w-5 h-5 text-danger shrink-0 mt-0.5" aria-hidden="true"></i>
                        <div class="min-w-0">
                            <p class="text-small font-semibold text-danger">Motivo del rechazo</p>
                            <p class="text-small text-danger mt-0.5" style="opacity:.85">
                                {{ $detailRequisition->rejection_comment }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Partidas --}}
                <div>
                    <h4 class="text-small font-semibold text-text-primary mb-3">
                        Partidas ({{ $detailRequisition->items->count() }})
                    </h4>
                    <div class="space-y-3">
                        @foreach($detailRequisition->items as $item)
                            <div class="flex justify-between items-center bg-surface-main/20 p-3 rounded-lg border border-border">
                                <div>
                                    <p class="font-medium text-small text-text-primary">
                                        {{ $item->product?->canonical_name ?? 'Producto desconocido' }}
                                    </p>
                                    <p class="text-xs-fluid text-text-muted">
                                        {{ number_format($item->quantity, 2) }}
                                        {{ $item->product?->measure?->code ?? $item->measure?->abbreviation ?? 'pza' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-small text-text-primary">
                                        ${{ number_format($item->line_total_computed, 2) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Totales --}}
                <div class="pt-4 border-t border-border">
                    <div class="flex justify-between items-center text-small mb-1">
                        <span class="text-text-muted">Subtotal</span>
                        <span class="font-medium text-text-primary">${{ number_format($detailRequisition->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-small mb-2">
                        <span class="text-text-muted">IVA (16%)</span>
                        <span class="font-medium text-text-primary">${{ number_format($detailRequisition->tax_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-body font-semibold text-text-primary">Total</span>
                        <span class="text-h3 font-bold text-text-primary tabular-nums">
                            ${{ number_format($detailRequisition->total, 2) }}
                        </span>
                    </div>
                </div>

                {{-- Acciones del Drawer --}}
                <div class="flex justify-end gap-3 pt-6 border-t border-border mt-auto">
                    <x-button as="a" href="{{ route('requisiciones.show', $detailRequisition->id) }}" variant="secondary" wire:navigate>
                        Ver Ficha Completa
                    </x-button>

                    @if($detailRequisition->status === 'pendiente' && (auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*')))
                        <x-button wire:click="openRejectModal" variant="secondary" icon="x-circle">
                            Rechazar
                        </x-button>
                        <x-button
                            @click="$dispatch('confirm-action', {
                                title: 'Aprobar Requisición',
                                description: 'Cambiará a estado Aprobada y se notificará al solicitante.',
                                confirmLabel: 'Aprobar',
                                variant: 'success',
                                action: 'approve',
                                params: [{{ $detailRequisition->id }}]
                            })"
                            variant="success" icon="check-circle">
                            Aprobar
                        </x-button>
                    @endif
                </div>
            </div>
        @else
            <div class="flex items-center justify-center h-48">
                <span class="spinner spinner-lg text-primary-600"></span>
            </div>
        @endif
    </x-drawer>

    {{-- Modal de Rechazo --}}
    @if($showRejectModal)
        <x-modal show="showRejectModal"
            title="Rechazar Requisición"
            subtitle="Indica el motivo del rechazo (obligatorio)"
            maxWidth="md">
            <form wire:submit="confirmReject" class="p-5 space-y-4">
                <x-form-field label="Motivo del rechazo" required error="{{ $errors->first('rejectionComment') }}">
                    <textarea wire:model="rejectionComment"
                        class="input"
                        rows="3"
                        placeholder="Explica por qué esta requisición fue rechazada..."
                        aria-required="true"></textarea>
                </x-form-field>
                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showRejectModal', false)" variant="secondary">
                        Cancelar
                    </x-button>
                    <x-button type="submit" variant="danger" icon="x-circle">
                        Confirmar Rechazo
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- Confirmación global para Aprobar --}}
    <x-confirm-modal />
</div>
