<div>
    {{-- Header --}}
    <x-page-header subtitle="Requisiciones" title="Detalle de Requisición">
        <x-slot:actions>
            <x-button href="{{ route('requisiciones.index') }}" variant="secondary" icon="arrow-left" wire:navigate>
                Volver
            </x-button>
            <x-button href="{{ route('requisiciones.pdf', $requisition->id) }}" target="_blank" variant="secondary" icon="printer">
                Imprimir
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-6">
        {{-- Resumen --}}
        <div class="card p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-h2 font-semibold text-text-primary">
                        {{ $requisition->number ?? 'REQ-' . str_pad($requisition->id, 5, '0', STR_PAD_LEFT) }}
                    </h2>
                    <p class="text-small text-text-muted mt-1">{{ $requisition->date?->format('d/m/Y') }}</p>
                </div>
                <x-status-badge :status="$requisition->status" :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <span class="text-xs-fluid text-text-muted block mb-1">Proyecto</span>
                    <span
                        class="text-small font-medium text-text-primary">{{ $requisition->project->name ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-xs-fluid text-text-muted block mb-1">Solicitante</span>
                    <span
                        class="text-small font-medium text-text-primary">{{ $requisition->creator->name ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-xs-fluid text-text-muted block mb-1">Proveedor</span>
                    <span
                        class="text-small font-medium text-text-primary">{{ $requisition->vendor?->name ?? '—' }}</span>
                </div>
                @if($requisition->annotations)
                    <div class="col-span-2 md:col-span-3">
                        <span class="text-xs-fluid text-text-muted block mb-1">Notas</span>
                        <span class="text-small text-text-primary">{{ $requisition->annotations }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Productos --}}
        <div class="card p-0 overflow-hidden">
            <div class="px-6 py-5 flex items-center gap-2">
                <h2 class="text-h2 text-text-primary">Productos Solicitados</h2>
                <x-badge variant="secondary">{{ $requisition->items->count() }}</x-badge>
            </div>
            <div class="table-embedded border-t-0 border-x-0 rounded-none">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="text-right">Cant.</th>
                            <th class="text-right">Precio U.</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requisition->items as $item)
                            <tr class="hover:bg-surface-hover/30">
                                <td>
                                    <p class="font-medium text-small">{{ $item->product?->canonical_name ?? 'Producto no encontrado' }}</p>
                                </td>
                                <td>
                                    <span class="text-small text-text-muted">{{ $item->product?->category?->name ?? '—' }}</span>
                                </td>
                                <td class="text-right tabular-nums text-small">
                                    {{ number_format($item->quantity, 2) }}
                                    {{ $item->measure?->abbreviation ?? '' }}
                                </td>
                                <td class="text-right tabular-nums text-small">
                                    ${{ number_format($item->unit_price, 2) }}
                                </td>
                                <td class="text-right font-medium tabular-nums text-small">
                                    ${{ number_format($item->line_total_computed, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="flex flex-col items-center justify-center py-10 gap-2">
                                        <div class="w-10 h-10 rounded-full bg-surface-main flex items-center justify-center">
                                            <i data-lucide="package" class="w-5 h-5 text-text-muted"></i>
                                        </div>
                                        <p class="text-small text-text-muted">Sin productos registrados</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Totales --}}
            <div class="flex justify-end px-6 py-5">
                <x-totals-summary>
                    <div class="flex items-center justify-between gap-6">
                        <span class="text-small text-text-muted">Subtotal</span>
                        <span
                            class="text-small font-medium text-text-secondary tabular-nums">${{ number_format($requisition->subtotal, 2) }}</span>
                    </div>
                    @php
                        $discountTotal = $requisition->items->sum('line_discount_total');
                    @endphp
                    @if($discountTotal > 0)
                        <div class="flex items-center justify-between gap-6">
                            <span class="text-small text-danger">Descuento</span>
                            <span
                                class="text-small font-medium text-danger tabular-nums">-${{ number_format($discountTotal, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-6">
                        <span class="text-small text-text-muted">IVA</span>
                        <span
                            class="text-small font-medium text-text-muted tabular-nums">${{ number_format($requisition->tax_amount, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-6 pt-3 mt-1 border-t border-border">
                        <span class="text-body font-semibold text-text-primary">Total</span>
                        <span
                            class="text-h3 font-bold text-text-primary tabular-nums">${{ number_format($requisition->total, 2) }}</span>
                    </div>
                </x-totals-summary>
            </div>
        </div>
    </div>
</div>