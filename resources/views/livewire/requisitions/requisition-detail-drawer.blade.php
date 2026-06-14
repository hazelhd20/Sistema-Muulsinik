<div>
    {{-- Drawer de Detalle Rápido --}}
    <x-drawer show="showDetailDrawer" title="Detalles de Requisición" maxWidth="xl" x-on:open-requisition-detail.window="$wire.set('showDetailDrawer', true)">
        {{-- Skeleton Loading --}}
        <div wire:loading wire:target="showDetail" class="space-y-6">
            <div class="flex items-start justify-between">
                <div class="space-y-2 w-1/3">
                    <div class="h-6 bg-surface-hover rounded animate-pulse"></div>
                    <div class="h-4 bg-surface-hover rounded w-2/3 animate-pulse"></div>
                </div>
                <div class="h-6 bg-surface-hover rounded w-20 animate-pulse"></div>
            </div>
            <div class="h-24 bg-surface-hover rounded-xl animate-pulse"></div>
            <div class="space-y-3">
                <div class="h-5 bg-surface-hover rounded w-1/4 animate-pulse"></div>
                <div class="h-16 bg-surface-hover rounded-lg animate-pulse"></div>
                <div class="h-16 bg-surface-hover rounded-lg animate-pulse"></div>
            </div>
        </div>

        <div wire:loading.remove wire:target="showDetail">
            @if($detailRequisition)
                <div class="space-y-6">
                    {{-- Resumen principal --}}
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-h3 text-text-primary">
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
                        <p class="text-text-muted text-xs font-medium mb-0.5">Proyecto</p>
                        <p class="font-medium text-text-primary">{{ $detailRequisition->project?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-text-muted text-xs font-medium mb-0.5">Solicitante</p>
                        <p class="font-medium text-text-primary">{{ $detailRequisition->creator?->name ?? '—' }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-text-muted text-xs font-medium mb-0.5">Proveedor Seleccionado</p>
                        <p class="font-medium text-text-primary">
                            {{ $detailRequisition->vendor?->trade_name ?? $detailRequisition->vendor?->name ?? '—' }}
                        </p>
                    </div>

                    @if($detailRequisition->approver && in_array($detailRequisition->status, ['aprobada', 'rechazada']))
                        <div class="col-span-2">
                            <p class="text-text-muted text-xs font-medium mb-0.5">
                                {{ $detailRequisition->status === 'aprobada' ? 'Aprobada por' : 'Rechazada por' }}
                            </p>
                            <p class="font-medium text-text-primary">{{ $detailRequisition->approver->name }}</p>
                        </div>
                    @endif
                </div>

                {{-- Banner de rechazo --}}
                @if($detailRequisition->status === 'rechazada' && $detailRequisition->rejection_comment)
                    <div class="flex items-start gap-3 p-4 rounded-xl bg-danger-light border border-danger-border">
                        <x-lucide-x-octagon class="w-5 h-5 text-danger shrink-0 mt-0.5" aria-hidden="true" />
                        <div class="min-w-0">
                            <p class="text-small font-semibold text-danger">Motivo del rechazo</p>
                            <p class="text-small text-danger mt-0.5" style="opacity:.85">
                                {{ $detailRequisition->rejection_comment }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Partidas --}}
                <div class="mt-8 mb-6">
                    <h4 class="text-xs font-semibold text-text-muted mb-2 uppercase tracking-wider">
                        Partidas ({{ $detailRequisition->items->count() }})
                    </h4>
                    <div class="flex flex-col gap-3">
                        @foreach($detailRequisition->items as $item)
                            <div class="flex justify-between items-center bg-surface-main/30 border border-border/50 rounded-xl p-3">
                                <div>
                                    <p class="font-medium text-small text-text-primary leading-snug mb-1">
                                        {{ $item->product?->canonical_name ?? 'Producto desconocido' }}
                                    </p>
                                    <p class="text-xs font-medium text-text-muted">
                                        {{ number_format($item->quantity, 2) }} {{ $item->product?->measure?->code ?? $item->measure?->abbreviation ?? 'pza' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-small text-text-primary tabular-nums">
                                        ${{ number_format($item->line_subtotal_computed, 2) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Totales --}}
                <div class="pt-2">
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

                {{-- (Acciones movidas al slot :footer del drawer) --}}
                </div>
            @endif
        </div>
        
        @if($detailRequisition)
        <x-slot:footer>
            <div class="flex justify-end gap-3" wire:loading.remove wire:target="showDetail">
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
                            onConfirmCallback: () => $wire.approve({{ $detailRequisition->id }})
                        })"
                        variant="success" icon="check-circle">
                        Aprobar
                    </x-button>
                @endif
            </div>
        </x-slot:footer>
        @endif

        @if(!$detailRequisition)
            <div wire:loading.remove wire:target="showDetail" class="flex items-center justify-center h-48">
                <span class="spinner spinner-lg text-primary-600"></span>
            </div>
        @endif
    </x-drawer>

    {{-- Modal de Rechazo (extraído a partial compartido) --}}
    @include('livewire.requisitions._reject-modal')
</div>
