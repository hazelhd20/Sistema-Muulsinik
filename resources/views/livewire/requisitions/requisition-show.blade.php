<div>
    {{-- ─── Header con acciones de workflow ─── --}}
    @php
        $breadcrumbs = [
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Requisiciones', 'url' => route('requisiciones.index')],
            ['label' => $requisition->number ?? 'REQ-' . str_pad($requisition->id, 5, '0', STR_PAD_LEFT)]
        ];
    @endphp
    <x-page-header :breadcrumbs="$breadcrumbs" :status="$requisition->status">
        <x-slot:title>
            <div class="flex items-center gap-3">
                {{ $requisition->number ?? 'REQ-' . str_pad($requisition->id, 5, '0', STR_PAD_LEFT) }}
            </div>
        </x-slot:title>
        <x-slot:actions>
            {{-- Acciones secundarias siempre visibles --}}
            <x-button href="{{ route('requisiciones.pdf', $requisition->id) }}" target="_blank" variant="secondary" icon="printer">
                Imprimir
            </x-button>

            {{-- ── Workflow: Borrador → Pendiente (o Aprobada si admin) ── --}}
            @if($requisition->status === 'borrador' && $requisition->created_by === auth()->id())
                @if(auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*'))
                    <x-button
                        @click="$dispatch('confirm-action', {
                            title: 'Aprobar Requisición',
                            description: 'Tienes permisos de aprobación. La requisición se aprobará automáticamente.',
                            confirmLabel: 'Aprobar ahora',
                            variant: 'success',
                            action: 'submitForApproval',
                            params: []
                        })"
                        variant="success" icon="check-circle">
                        Aprobar Requisición
                    </x-button>
                @else
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
            @endif

            {{-- ── Workflow: Pendiente → Aprobada / Rechazada ── --}}
            @if((auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*')) && $requisition->status === 'pendiente')
                <x-button wire:click="openRejectModal" variant="secondary" icon="x-circle" target="openRejectModal">
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
            <x-lucide-x-octagon class="w-5 h-5 text-danger shrink-0 mt-0.5" aria-hidden="true" />
            <div class="min-w-0">
                <p class="text-small font-semibold text-danger">Requisición rechazada</p>
                <p class="text-small text-danger mt-0.5 opacity-85">{{ $requisition->rejection_comment }}</p>
                @if($requisition->approver)
                    <p class="text-xs text-danger mt-1 opacity-60">
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

        <x-card>
            <x-card.header title="Detalles Generales" />
            <x-card.body>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <x-data-label label="Fecha" :value="$requisition->date?->format('d/m/Y') ?? '—'" />
                    <x-data-label label="Proyecto" :value="$requisition->project?->name ?? '—'" />
                    <x-data-label label="Solicitante" :value="$requisition->creator?->name ?? '—'" />
                    @php
                        $proveedorName = $requisition->vendor?->supplier?->trade_name 
                            ?? $requisition->vendor?->name 
                            ?? $requisition->items->first()?->supplier?->trade_name 
                            ?? '—';
                    @endphp
                    <x-data-label label="Proveedor sugerido" :value="$proveedorName" />

                    @if($requisition->approver && in_array($requisition->status, ['aprobada', 'rechazada']))
                        <x-data-label 
                            :label="$requisition->status === 'aprobada' ? 'Aprobada por' : 'Rechazada por'" 
                            :value="$requisition->approver->name" />
                    @endif

                    @if($requisition->annotations)
                        <div class="col-span-2 md:col-span-4">
                            <x-data-label label="Notas adicionales">
                                {{ $requisition->annotations }}
                            </x-data-label>
                        </div>
                    @endif
                </div>
            </x-card.body>
        </x-card>

        {{-- ─── Tarjeta de productos ─── --}}
        <x-card class="overflow-hidden">
            <x-card.header title="Productos Solicitados">
                <x-slot:action>
                    <x-badge variant="secondary" class="shrink-0">{{ $requisition->items->count() }}</x-badge>
                </x-slot:action>
            </x-card.header>

            {{-- Desktop Table --}}
            <x-card.table class="hidden md:block">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-hover/50 border-y border-border text-xs font-semibold text-text-secondary">
                        <tr>
                            <th class="pl-6 pr-4 py-3 whitespace-nowrap">Producto</th>
                            <th class="px-4 py-3 whitespace-nowrap">Categoría</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap w-[10%]">Cant.</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">Precio U.</th>
                            <th class="pr-6 pl-4 py-3 text-right whitespace-nowrap">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border border-b border-border">
                        @forelse($requisition->items as $item)
                            <tr class="hover:bg-surface-hover/30 transition-colors">
                                <td class="pl-6 pr-4 py-3">
                                    <p class="font-medium text-small text-text-primary">
                                        {{ $item->product?->canonical_name ?? 'Producto no encontrado' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-small text-text-secondary">
                                        {{ $item->product?->category?->name ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums text-small text-text-secondary">
                                    {{ number_format($item->quantity, 2) }}
                                    {{ $item->measure?->abbreviation ?? '' }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-small text-text-secondary">
                                    ${{ number_format($item->unit_price, 2) }}
                                </td>
                                <td class="pr-6 pl-4 py-3 text-right font-medium tabular-nums text-small text-text-primary">
                                    ${{ number_format($item->line_total_computed, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center">
                                    <x-empty-state icon="package" title="Sin productos registrados" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-card.table>

            {{-- Mobile Cards --}}
            <div class="md:hidden flex flex-col divide-y divide-border/60 border-t border-border/60">
                @forelse($requisition->items as $item)
                    <div class="p-4 flex flex-col gap-3 bg-surface-card hover:bg-surface-hover/50 transition-colors">
                        <div class="flex justify-between items-start gap-4">
                            <div class="min-w-0">
                                <p class="font-semibold text-body text-text-primary leading-snug">
                                    {{ $item->product?->canonical_name ?? 'Producto no encontrado' }}
                                </p>
                                <p class="text-xs font-medium text-text-muted mt-1 uppercase tracking-wider">
                                    {{ $item->product?->category?->name ?? 'Sin categoría' }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mt-1 pt-3 border-t border-border/40">
                            <div>
                                <p class="text-[10px] text-text-muted uppercase font-semibold tracking-wider mb-1">Cantidad</p>
                                <p class="text-sm font-medium text-text-primary">
                                    {{ number_format($item->quantity, 2) }} {{ $item->measure?->abbreviation ?? '' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-text-muted uppercase font-semibold tracking-wider mb-1">Precio Unitario</p>
                                <p class="text-sm font-medium text-text-primary">
                                    ${{ number_format($item->unit_price, 2) }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center mt-1 pt-3 border-t border-border/40">
                            <span class="text-xs font-semibold text-text-muted uppercase tracking-wider">Total Línea</span>
                            <span class="font-bold text-lg text-text-primary tabular-nums">
                                ${{ number_format($item->line_total_computed, 2) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center bg-surface-main/30">
                        <x-lucide-package class="w-8 h-8 mx-auto text-text-muted/50 mb-3" />
                        <span class="text-sm font-medium text-text-muted">No hay productos registrados</span>
                    </div>
                @endforelse
            </div>

            {{-- Totales --}}
            <div class="flex justify-end px-6 pt-6 pb-8">
                <x-totals-summary>
                    <div class="flex items-center justify-between gap-6">
                        <span class="text-small text-text-secondary">Subtotal</span>
                        <span class="text-small font-medium text-text-primary tabular-nums">
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
                        <span class="text-small text-text-secondary">IVA</span>
                        <span class="text-small font-medium text-text-primary tabular-nums">
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
        </x-card>
    </div>

    {{-- ─── Historial de Actividad (Audit Log) (H1) ─── --}}
    @if($requisition->activities->isNotEmpty())
        <x-card class="mt-6 mb-6">
            <x-card.header title="Historial de Actividad" />
            <x-card.body>
                <div class="relative space-y-6 before:absolute before:inset-0 before:ml-5 before:-translate-x-1/2 before:h-full before:w-px before:bg-border">
                    @foreach($requisition->activities as $activity)
                        <x-activity-timeline-item :activity="$activity" />
                    @endforeach
                </div>
            </x-card.body>
        </x-card>
    @endif

    {{-- ─── Modal de Rechazo (RF-REQ-09) ─── --}}
    @include('livewire.requisitions._reject-modal')

    {{-- ─── Diálogo de confirmación global ─── --}}
    <x-confirm-modal />
</div>
