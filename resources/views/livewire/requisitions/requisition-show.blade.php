<div>
    {{-- ─── Header con acciones de workflow ─── --}}
    <x-page-header subtitle="Requisiciones" title="Detalle de Requisición">
        <x-slot:actions>
            {{-- Acciones secundarias siempre visibles --}}
            <x-button href="{{ route('requisiciones.index') }}" variant="secondary" icon="arrow-left" wire:navigate>
                Volver
            </x-button>
            <x-button href="{{ route('requisiciones.pdf', $requisition->id) }}" target="_blank" variant="secondary" icon="printer">
                Imprimir
            </x-button>

            {{-- ── Workflow: Borrador → Pendiente (o Aprobada si admin) ── --}}
            @if($requisition->status === 'borrador')
                <x-button
                    @click="$dispatch('confirm-action', {
                        title: 'Solicitar Aprobación',
                        description: 'La requisición será enviada a los aprobadores del sistema.',
                        confirmLabel: 'Enviar a aprobación',
                        variant: 'primary',
                        action: 'submitForApproval',
                        params: []
                    })"
                    variant="primary" icon="send">
                    Solicitar Aprobación
                </x-button>
            @endif

            {{-- ── Workflow: Pendiente → Aprobada / Rechazada ── --}}
            @if($requisition->status === 'pendiente' && (auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*')))
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
                        params: []
                    })"
                    variant="success" icon="check-circle">
                    Aprobar
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- ─── Banner de rechazo (visible solo cuando rechazada) ─── --}}
    @if($requisition->status === 'rechazada' && $requisition->rejection_comment)
        <div class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-danger-light border border-danger-border">
            <i data-lucide="x-octagon" class="w-5 h-5 text-danger shrink-0 mt-0.5" aria-hidden="true"></i>
            <div class="min-w-0">
                <p class="text-small font-semibold text-danger">Requisición rechazada</p>
                <p class="text-small text-danger mt-0.5" style="opacity:.85">{{ $requisition->rejection_comment }}</p>
                @if($requisition->approver)
                    <p class="text-xs-fluid text-danger mt-1" style="opacity:.6">
                        Por {{ $requisition->approver->name }}
                        @if($requisition->updated_at)
                            &middot; {{ $requisition->updated_at->locale('es')->diffForHumans() }}
                        @endif
                    </p>
                @endif
            </div>
        </div>
    @endif

    <div class="space-y-6">

        {{-- ─── Tarjeta de resumen ─── --}}
        <div class="card p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-h2 font-semibold text-text-primary">
                        {{ $requisition->number ?? 'REQ-' . str_pad($requisition->id, 5, '0', STR_PAD_LEFT) }}
                    </h2>
                    <p class="text-small text-text-muted mt-1">{{ $requisition->date?->format('d/m/Y') }}</p>
                </div>
                <x-status-badge
                    :status="$requisition->status"
                    :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <span class="text-xs-fluid text-text-muted block mb-1">Proyecto</span>
                    <span class="text-small font-medium text-text-primary">{{ $requisition->project?->name ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-xs-fluid text-text-muted block mb-1">Solicitante</span>
                    <span class="text-small font-medium text-text-primary">{{ $requisition->creator?->name ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-xs-fluid text-text-muted block mb-1">Proveedor</span>
                    <span class="text-small font-medium text-text-primary">{{ $requisition->vendor?->name ?? '—' }}</span>
                </div>

                {{-- Quién aprobó / rechazó --}}
                @if($requisition->approver && in_array($requisition->status, ['aprobada', 'rechazada']))
                    <div>
                        <span class="text-xs-fluid text-text-muted block mb-1">
                            {{ $requisition->status === 'aprobada' ? 'Aprobada por' : 'Rechazada por' }}
                        </span>
                        <span class="text-small font-medium text-text-primary">{{ $requisition->approver->name }}</span>
                    </div>
                @endif

                @if($requisition->annotations)
                    <div class="col-span-2 md:col-span-3">
                        <span class="text-xs-fluid text-text-muted block mb-1">Notas</span>
                        <span class="text-small text-text-primary">{{ $requisition->annotations }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- ─── Tarjeta de productos ─── --}}
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
                                    <p class="font-medium text-small">
                                        {{ $item->product?->canonical_name ?? 'Producto no encontrado' }}
                                    </p>
                                </td>
                                <td>
                                    <span class="text-small text-text-muted">
                                        {{ $item->product?->category?->name ?? '—' }}
                                    </span>
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
                                    <x-empty-state icon="package" title="Sin productos registrados" />
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
                        <span class="text-small font-medium text-text-secondary tabular-nums">
                            ${{ number_format($requisition->subtotal, 2) }}
                        </span>
                    </div>

                    @php $discountTotal = $requisition->items->sum('line_discount_total'); @endphp
                    @if($discountTotal > 0)
                        <div class="flex items-center justify-between gap-6">
                            <span class="text-small text-danger">Descuento</span>
                            <span class="text-small font-medium text-danger tabular-nums">
                                -${{ number_format($discountTotal, 2) }}
                            </span>
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-6">
                        <span class="text-small text-text-muted">IVA</span>
                        <span class="text-small font-medium text-text-muted tabular-nums">
                            ${{ number_format($requisition->tax_amount, 2) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-6 pt-3 mt-1 border-t border-border">
                        <span class="text-body font-semibold text-text-primary">Total</span>
                        <span class="text-h3 font-bold text-text-primary tabular-nums">
                            ${{ number_format($requisition->total, 2) }}
                        </span>
                    </div>
                </x-totals-summary>
            </div>
        </div>
    </div>

    {{-- ─── Modal de Rechazo (RF-REQ-09) ─── --}}
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

    {{-- ─── Diálogo de confirmación global ─── --}}
    <x-confirm-modal />
</div>
