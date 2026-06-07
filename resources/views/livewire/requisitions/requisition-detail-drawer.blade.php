<div>
    {{-- Drawer de Detalle Rápido --}}
    <x-drawer show="showDetailDrawer" title="Detalles de Requisición" maxWidth="xl">
        @if($detailRequisition)
            <div class="space-y-6">
                {{-- Resumen principal --}}
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-h3 font-semibold text-text-primary">{{ $detailRequisition->number ?? 'REQ-' . str_pad($detailRequisition->id, 5, '0', STR_PAD_LEFT) }}</h3>
                        <p class="text-sm text-text-muted mt-1">Creada el {{ $detailRequisition->date?->format('d \d\e F, Y') }}</p>
                    </div>
                    <x-status-badge :status="$detailRequisition->status" :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
                </div>

                {{-- Detalles en grid --}}
                <div class="grid grid-cols-2 gap-4 text-sm bg-surface-main/30 p-4 rounded-xl border border-border">
                    <div>
                        <p class="text-text-muted text-xs-fluid font-medium mb-0.5">Proyecto</p>
                        <p class="font-medium text-text-primary">{{ $detailRequisition->project?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-text-muted text-xs-fluid font-medium mb-0.5">Creador</p>
                        <p class="font-medium text-text-primary">{{ $detailRequisition->creator?->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-text-muted text-xs-fluid font-medium mb-0.5">Proveedor Seleccionado</p>
                        <p class="font-medium text-text-primary">{{ $detailRequisition->vendor?->trade_name ?? $detailRequisition->vendor?->name ?? 'Ninguno' }}</p>
                    </div>
                </div>

                {{-- Partidas --}}
                <div>
                    <h4 class="text-sm font-semibold text-text-primary mb-3">Partidas ({{ $detailRequisition->items->count() }})</h4>
                    <div class="space-y-3">
                        @foreach($detailRequisition->items as $item)
                            <div class="flex justify-between items-center bg-surface-main/20 p-3 rounded-lg border border-border">
                                <div>
                                    <p class="font-medium text-sm text-text-primary">{{ $item->product?->canonical_name ?? 'Producto desconocido' }}</p>
                                    <p class="text-xs text-text-muted">{{ $item->quantity }} {{ $item->product?->measure?->code ?? $item->measure?->abbreviation ?? 'pza' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-sm text-text-primary">${{ number_format($item->line_total_computed, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Totales --}}
                <div class="pt-4 border-t border-border">
                    <div class="flex justify-between items-center text-sm mb-1">
                        <span class="text-text-muted">Subtotal</span>
                        <span class="font-medium text-text-primary">${{ number_format($detailRequisition->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm mb-2">
                        <span class="text-text-muted">IVA (16%)</span>
                        <span class="font-medium text-text-primary">${{ number_format($detailRequisition->tax_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-base font-semibold text-text-primary">Total</span>
                        <span class="text-lg font-bold text-primary-600">${{ number_format($detailRequisition->total, 2) }}</span>
                    </div>
                </div>

                {{-- Acciones del Drawer --}}
                <div class="flex justify-end gap-3 pt-6 border-t border-border mt-auto">
                    <x-button as="a" href="{{ route('requisiciones.show', $detailRequisition->id) }}" variant="secondary" wire:navigate>
                        Ver Ficha Completa
                    </x-button>

                    @if($detailRequisition->status === 'pendiente' && (auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*')))
                        <x-button wire:click="approve({{ $detailRequisition->id }})" variant="success" icon="check-circle">
                            Aprobar
                        </x-button>
                    @endif
                </div>
            </div>
        @else
            <div class="flex items-center justify-center h-48">
                <div class="w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
            </div>
        @endif
    </x-drawer>
</div>
