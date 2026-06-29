<div>
    {{-- ─── Header con acciones de workflow ─── --}}
    @php
        $folio = $requisition->number ?? 'REQ-' . str_pad($requisition->id, 5, '0', STR_PAD_LEFT);
        $breadcrumbs = [
            ['label' => 'Requisiciones', 'url' => route('requisiciones.index')],
            ['label' => $folio]
        ];
    @endphp
    <x-page-header :breadcrumbs="$breadcrumbs" :title="$folio" :status="$requisition->status" :sticky="true">
        <x-slot:actions>
            {{-- Acción secundaria siempre visible --}}
            <x-button href="{{ route('requisiciones.pdf', $requisition->id) }}" target="_blank" variant="secondary"
                icon="printer">
                Imprimir
            </x-button>

            {{-- ── Workflow: Borrador → Pendiente (o Aprobada si admin) ── --}}
            @if($requisition->status === 'borrador' && $requisition->created_by === auth()->id())
                @if(auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*'))
                    <x-button @click="$dispatch('confirm-action', {
                                    title: 'Aprobar Requisición',
                                    description: 'Tienes permisos de aprobación. La requisición se aprobará automáticamente.',
                                    confirmLabel: 'Aprobar ahora',
                                    variant: 'success',
                                    action: 'submitForApproval',
                                    params: []
                                })" variant="success" icon="check-circle">
                        Aprobar Requisición
                    </x-button>
                @else
                    <x-button @click="$dispatch('confirm-action', {
                                    title: 'Solicitar Aprobación',
                                    description: 'La requisición será enviada a los aprobadores del sistema.',
                                    confirmLabel: 'Enviar a aprobación',
                                    variant: 'primary',
                                    action: 'submitForApproval',
                                    params: []
                                })" variant="primary" icon="send">
                        Solicitar Aprobación
                    </x-button>
                @endif
            @endif

            {{-- ── Workflow: Pendiente → Aprobada / Rechazada ── --}}
            @if((auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*')) && $requisition->status === 'pendiente')
                <x-button wire:click="openRejectModal" variant="secondary" icon="x-circle" target="openRejectModal">
                    Rechazar
                </x-button>
                <x-button @click="$dispatch('confirm-action', {
                            title: 'Aprobar Requisición',
                            description: 'Cambiará a estado Aprobada y se notificará al solicitante.',
                            confirmLabel: 'Aprobar',
                            variant: 'success',
                            action: 'approve',
                            params: []
                        })" variant="success" icon="check-circle">
                    Aprobar
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>



    {{-- ─── Banner de rechazo (visible solo cuando rechazada) ─── --}}
    @if($requisition->status === 'rechazada' && $requisition->rejection_comment)
        <x-alert variant="danger" icon="x-octagon" title="Requisición rechazada" class="mb-6">
            {{ $requisition->rejection_comment }}

            @if($requisition->approver)
                <x-slot:footer>
                    Por {{ $requisition->approver->name }}
                    @if($requisition->updated_at)
                        &middot; {{ $requisition->updated_at->locale('es')->diffForHumans() }}
                    @endif
                </x-slot:footer>
            @endif
        </x-alert>
    @endif

    <div class="space-y-6">

        <x-card class="mb-6">
            <x-card.header title="Detalles Generales" />
            <x-card.body>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <x-data-label label="Fecha">
                        <div class="flex items-center gap-1.5">
                            <x-lucide-calendar class="w-3.5 h-3.5 text-text-muted/70" />
                            <span>{{ $requisition->date?->format('d/m/Y') ?? '—' }}</span>
                        </div>
                    </x-data-label>
                    <x-data-label label="Proyecto">
                        <div class="flex items-center gap-1.5">
                            <x-lucide-hard-hat class="w-3.5 h-3.5 text-text-muted/70" />
                            <span>{{ $requisition->project?->name ?? '—' }}</span>
                        </div>
                    </x-data-label>
                    <x-data-label label="Solicitante">
                        <div class="flex items-center gap-1.5">
                            <x-lucide-user class="w-3.5 h-3.5 text-text-muted/70" />
                            <span>{{ $requisition->creator?->name ?? '—' }}</span>
                        </div>
                    </x-data-label>
                    @php
                        $proveedorName = $requisition->vendor?->supplier?->trade_name
                            ?? $requisition->vendor?->name
                            ?? $requisition->items->first()?->supplier?->trade_name
                            ?? '—';
                    @endphp
                    <x-data-label label="Proveedor">
                        <div class="flex items-center gap-1.5">
                            <x-lucide-truck class="w-3.5 h-3.5 text-text-muted/70" />
                            <span>{{ $proveedorName }}</span>
                        </div>
                    </x-data-label>

                    @if($requisition->approver && in_array($requisition->status, ['aprobada', 'rechazada']))
                        <x-data-label label="{{ $requisition->status === 'aprobada' ? 'Aprobada por' : 'Rechazada por' }}">
                            <div class="flex items-center gap-1.5">
                                <x-dynamic-component :component="$requisition->status === 'aprobada' ? 'lucide-user-check' : 'lucide-user-x'" class="w-3.5 h-3.5 text-text-muted/70" />
                                <span>{{ $requisition->approver->name }}</span>
                            </div>
                        </x-data-label>
                    @endif

                    @if($requisition->annotations)
                        <div class="col-span-2 md:col-span-4">
                            <x-data-label label="Notas adicionales">
                                <div class="flex items-start gap-1.5">
                                    <x-lucide-sticky-note class="w-3.5 h-3.5 text-text-muted/70 mt-0.5 shrink-0" />
                                    <span>{{ $requisition->annotations }}</span>
                                </div>
                            </x-data-label>
                        </div>
                    @endif
                </div>
            </x-card.body>
        </x-card>

        {{-- ─── Tarjeta de productos ─── --}}
        <x-card class="mb-6 overflow-hidden">
            <x-card.header title="Productos Solicitados">
                @if($requisition->items->count() > 0)
                    <x-slot:action>
                        <x-badge variant="secondary" size="md" icon="package" :normal-case="true">
                            {{ $requisition->items->count() }}
                            {{ $requisition->items->count() === 1 ? 'artículo' : 'artículos' }}
                        </x-badge>
                    </x-slot:action>
                @endif
            </x-card.header>

            {{-- Desktop Table --}}
            <div class="hidden md:block w-full overflow-x-auto">
                @if($requisition->items->isNotEmpty())
                    <table class="w-full text-left border-collapse">
                        <thead
                            class="bg-surface-main border-b border-border/40 text-xs font-semibold text-text-muted uppercase tracking-wider">
                            <tr>
                                <th class="pl-6 pr-4 py-3 whitespace-nowrap">Producto</th>
                                <th class="px-4 py-3 whitespace-nowrap">Categoría</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap w-[10%]">Cant.</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Precio U.</th>
                                <th class="pr-6 pl-4 py-3 text-right whitespace-nowrap">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/40 border-b border-border/40">
                            @foreach($requisition->items as $item)
                                <tr class="hover:bg-surface-hover transition-colors duration-150">
                                    <td class="pl-6 pr-4 py-3">
                                        <p class="font-medium text-small text-text-primary">
                                            {{ $item->product?->canonical_name ?? 'Producto no encontrado' }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item->product?->category?->name)
                                            <x-dynamic-badge :value="$item->product->category->name" size="xs" />
                                        @else
                                            <span class="text-small text-text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center tabular-nums text-small text-text-secondary">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span>{{ number_format($item->quantity, 2) }}</span>
                                            <x-badge variant="secondary" size="xs">
                                                {{ $item->product?->measure?->abbreviation ?? $item->measure?->abbreviation ?? 'pza' }}
                                            </x-badge>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums text-small text-text-secondary">
                                        ${{ number_format($item->unit_price, 2) }}
                                    </td>
                                    <td class="pr-6 pl-4 py-3 text-right font-medium tabular-nums text-small text-text-primary">
                                        ${{ number_format($item->line_total_computed, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="py-12 px-6 flex justify-center">
                        <x-empty-state icon="package" title="Sin productos registrados" />
                    </div>
                @endif
            </div>

            {{-- Mobile Cards --}}
            <div class="md:hidden flex flex-col divide-y divide-border/40 border-t border-border/40">
                @if($requisition->items->isNotEmpty())
                    @foreach($requisition->items as $item)
                        <div class="p-4 flex flex-col gap-3 bg-surface-card hover:bg-surface-hover transition-colors duration-150">
                            <div class="flex justify-between items-start gap-4">
                                <div class="min-w-0">
                                    <p class="font-semibold text-small text-text-primary break-words">
                                        {{ $item->product?->canonical_name ?? 'Producto no encontrado' }}
                                    </p>
                                    <div class="mt-1">
                                        @if($item->product?->category?->name)
                                            <x-dynamic-badge :value="$item->product->category->name" size="xs" />
                                        @else
                                            <span class="text-xs text-text-muted">—</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mt-1 pt-3 border-t border-border/40">
                                <x-data-label label="Cantidad">
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span>{{ number_format($item->quantity, 2) }}</span>
                                        <x-badge variant="secondary" size="xs">
                                            {{ $item->product?->measure?->abbreviation ?? $item->measure?->abbreviation ?? 'pza' }}
                                        </x-badge>
                                    </div>
                                </x-data-label>

                                <x-data-label label="Precio Unitario" align="right">
                                    ${{ number_format($item->unit_price, 2) }}
                                </x-data-label>
                            </div>

                            <div class="flex justify-between items-center mt-1 pt-3 border-t border-border/40">
                                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider">Total Línea</span>
                                <span class="font-bold text-lg text-text-primary tabular-nums">
                                    ${{ number_format($item->line_total_computed, 2) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="py-12 px-6 flex justify-center">
                        <x-empty-state icon="package" title="Sin productos registrados" />
                    </div>
                @endif
            </div>

            {{-- Totales --}}
            <div class="flex justify-end px-6 pt-6 pb-8 border-t border-border/40">
                <x-totals-summary class="w-full sm:w-1/2 md:w-1/3 min-w-[280px]">
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
                    <div class="flex items-center justify-between gap-6 pt-3 mt-1 border-t border-border/40">
                        <span class="text-body font-semibold text-text-primary">Total final</span>
                        <span class="text-2xl font-bold text-text-primary tabular-nums tracking-tight">
                            ${{ number_format($requisition->total, 2) }}
                        </span>
                    </div>
                </x-totals-summary>
            </div>
        </x-card>

    </div> {{-- End space-y-6 --}}
    {{-- ─── Historial de Actividad (Audit Log) ─── --}}
    @if($requisition->activities->isNotEmpty())
        <x-card class="mt-6 mb-6">
            <x-card.header title="Historial de Actividad" />
            <x-card.body>
                <div
                    class="relative space-y-6 before:absolute before:inset-0 before:ml-5 before:-translate-x-1/2 before:h-full before:w-px before:bg-border/40">
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