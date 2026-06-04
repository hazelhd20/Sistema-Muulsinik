<div>
    {{-- Header --}}
    <x-page-header subtitle="Requisiciones" title="Detalle de Requisición">
        <x-slot:actions>
            <a href="{{ route('requisiciones.index') }}" class="btn-secondary" wire:navigate>
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Volver
            </a>
            <a href="{{ route('requisiciones.pdf', $requisition->id) }}" target="_blank" class="btn-secondary">
                <i data-lucide="printer" class="w-4 h-4"></i>
                Imprimir
            </a>
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
                <x-status-badge :status="$requisition->estatus" :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
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
                @if($requisition->notes)
                    <div class="col-span-2 md:col-span-3">
                        <span class="text-xs-fluid text-text-muted block mb-1">Notas</span>
                        <span class="text-small text-text-primary">{{ $requisition->notes }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Productos --}}
        <div class="card p-0 overflow-hidden">
            <div class="px-6 py-5 flex items-center gap-2">
                <h2 class="text-h2 text-text-primary">Productos Solicitados</h2>
                <span class="badge badge-secondary">{{ $requisition->items->count() }}</span>
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
                                    <p class="font-medium text-small">{{ $item->product_name ?? $item->product?->name }}</p>
                                </td>
                                <td>
                                    <span class="text-small text-text-muted">{{ $item->category_name ?? '—' }}</span>
                                </td>
                                <td class="text-right tabular-nums text-small">
                                    {{ number_format($item->quantity, 2) }}
                                    {{ $item->measure_abbr ?? $item->measure?->abbreviation ?? '' }}
                                </td>
                                <td class="text-right tabular-nums text-small">
                                    ${{ number_format($item->unit_price, 2) }}
                                </td>
                                <td class="text-right font-medium tabular-nums text-small">
                                    ${{ number_format($item->total_price, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-6 text-text-muted">
                                    No hay productos registrados en esta requisición.
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
                    @if($requisition->discount > 0)
                        <div class="flex items-center justify-between gap-6">
                            <span class="text-small text-danger">Descuento</span>
                            <span
                                class="text-small font-medium text-danger tabular-nums">-${{ number_format($requisition->discount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-6">
                        <span class="text-small text-text-muted">IVA ({{ $requisition->tax_rate }}%)</span>
                        <span
                            class="text-small font-medium text-text-muted tabular-nums">${{ number_format($requisition->tax, 2) }}</span>
                    </div>
                    @if($requisition->retention_isr > 0 || $requisition->retention_iva > 0)
                        <div class="flex items-center justify-between gap-6">
                            <span class="text-small text-danger">Retenciones</span>
                            <span
                                class="text-small font-medium text-danger tabular-nums">-${{ number_format($requisition->retention_isr + $requisition->retention_iva, 2) }}</span>
                        </div>
                    @endif
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