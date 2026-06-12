<div>
    {{-- ─── Header con acciones de workflow ─── --}}
    <x-page-header subtitle="Requisiciones">
        <x-slot:title>
            <div class="flex items-center gap-3">
                {{ $requisition->number ?? 'REQ-' . str_pad($requisition->id, 5, '0', STR_PAD_LEFT) }}
                <x-status-badge
                    :status="$requisition->status"
                    :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
            </div>
        </x-slot:title>
        <x-slot:actions>
            {{-- Acciones secundarias siempre visibles --}}
            <x-button href="{{ route('requisiciones.index') }}" variant="secondary" icon="arrow-left" wire:navigate>
                Volver
            </x-button>
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

        {{-- ─── Tarjeta de metadatos ─── --}}
        <div class="card p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <span class="text-xs text-text-muted block mb-1">Fecha</span>
                    <span class="text-small font-medium text-text-primary">{{ $requisition->date?->format('d/m/Y') ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-xs text-text-muted block mb-1">Proyecto</span>
                    <span class="text-small font-medium text-text-primary">{{ $requisition->project?->name ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-xs text-text-muted block mb-1">Solicitante</span>
                    <span class="text-small font-medium text-text-primary">{{ $requisition->creator?->name ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-xs text-text-muted block mb-1">Proveedor sugerido</span>
                    <span class="text-small font-medium text-text-primary">{{ $requisition->vendor?->name ?? '—' }}</span>
                </div>

                @if($requisition->approver && in_array($requisition->status, ['aprobada', 'rechazada']))
                    <div>
                        <span class="text-xs text-text-muted block mb-1">
                            {{ $requisition->status === 'aprobada' ? 'Aprobada por' : 'Rechazada por' }}
                        </span>
                        <span class="text-small font-medium text-text-primary">{{ $requisition->approver->name }}</span>
                    </div>
                @endif

                @if($requisition->annotations)
                    <div class="col-span-2 md:col-span-4">
                        <span class="text-xs text-text-muted block mb-1">Notas adicionales</span>
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

            {{-- Desktop Table --}}
            <div class="table-embedded hidden md:block border-t-0 border-x-0 rounded-none overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="text-center w-[10%]">Cant.</th>
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
                                <td class="text-center tabular-nums text-small">
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

            {{-- Mobile Cards --}}
            <div class="md:hidden flex flex-col divide-y divide-border border-t border-border">
                @forelse($requisition->items as $item)
                    <div class="p-4 flex flex-col gap-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-bold text-body text-text-primary">
                                    {{ $item->product?->canonical_name ?? 'Producto no encontrado' }}
                                </p>
                                <p class="text-small text-text-muted mt-0.5">
                                    {{ $item->product?->category?->name ?? 'Sin categoría' }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-border/50">
                            <div>
                                <p class="text-xs text-text-muted mb-0.5">Cantidad</p>
                                <p class="text-small font-medium text-text-secondary">
                                    {{ number_format($item->quantity, 2) }} {{ $item->measure?->abbreviation ?? '' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-text-muted mb-0.5">Precio U.</p>
                                <p class="text-small font-medium text-text-secondary">
                                    ${{ number_format($item->unit_price, 2) }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center mt-2 bg-surface-main p-2 rounded-lg border border-border">
                            <span class="text-small font-medium text-text-secondary">Total Linea:</span>
                            <span class="font-bold text-text-primary">
                                ${{ number_format($item->line_total_computed, 2) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-6">
                        <x-empty-state icon="package" title="Sin productos registrados" />
                    </div>
                @endforelse
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

    {{-- ─── Historial de Actividad (Audit Log) (H1) ─── --}}
    @if($requisition->activities->isNotEmpty())
        <div class="card mb-6">
            <div class="px-6 py-4 border-b border-border flex items-center gap-3">
                <x-lucide-history class="w-5 h-5 text-text-muted" />
                <h2 class="text-h3 font-semibold text-text-primary">Historial de Actividad</h2>
            </div>
            <div class="p-6">
                <div class="relative space-y-6 before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:ml-8 md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-border before:to-transparent">
                    @foreach($requisition->activities as $activity)
                        @php
                            $actionConfig = match($activity->action) {
                                'created' => ['icon' => 'plus-circle', 'color' => 'text-primary-600', 'bg' => 'bg-primary-50', 'border' => 'border-primary-100'],
                                'approved' => ['icon' => 'check-circle', 'color' => 'text-success', 'bg' => 'bg-success-light', 'border' => 'border-success-border'],
                                'rejected' => ['icon' => 'x-circle', 'color' => 'text-danger', 'bg' => 'bg-danger-light', 'border' => 'border-danger-border'],
                                'status_changed' => ['icon' => 'arrow-right-circle', 'color' => 'text-warning', 'bg' => 'bg-warning-light', 'border' => 'border-warning-border'],
                                default => ['icon' => 'edit-3', 'color' => 'text-text-secondary', 'bg' => 'bg-surface-hover', 'border' => 'border-border-strong'],
                            };
                        @endphp
                        <div class="relative flex items-start gap-4 md:gap-6 group">
                            {{-- Line & Icon --}}
                            <div class="relative z-10 flex items-center justify-center w-10 h-10 md:w-12 md:h-12 rounded-full border shadow-sm shrink-0 bg-surface-card transition-transform group-hover:scale-105 {{ $actionConfig['border'] }}">
                                <div class="w-8 h-8 md:w-10 md:h-10 rounded-full flex items-center justify-center {{ $actionConfig['bg'] }}">
                                    <x-dynamic-component :component="'lucide-' . $actionConfig['icon']" class="w-4 h-4 md:w-5 md:h-5 {{ $actionConfig['color'] }}" />
                                </div>
                            </div>
                            
                            {{-- Content --}}
                            <div class="flex-1 min-w-0 pt-1 md:pt-2">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-1">
                                    <p class="text-small font-medium text-text-primary">
                                        {{ $activity->description ?? ucfirst(__($activity->action)) }}
                                    </p>
                                    <span class="text-xs text-text-muted whitespace-nowrap" title="{{ $activity->created_at->format('d/m/Y H:i:s') }}">
                                        {{ $activity->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="text-xs text-text-muted flex items-center gap-1.5">
                                    <x-lucide-user class="w-3.5 h-3.5" />
                                    {{ $activity->user ? $activity->user->name : 'Sistema' }}
                                </div>

                                @if($activity->old_values || $activity->new_values)
                                    <div class="mt-3 p-3 rounded-lg bg-surface-hover border border-border overflow-x-auto">
                                        @if($activity->action === 'status_changed')
                                            <div class="flex items-center gap-3 text-xs font-medium">
                                                <span class="text-text-muted line-through">{{ strtoupper($activity->old_values['status'] ?? '—') }}</span>
                                                <x-lucide-arrow-right class="w-3.5 h-3.5 text-text-secondary" />
                                                <span class="text-primary-600">{{ strtoupper($activity->new_values['status'] ?? '—') }}</span>
                                            </div>
                                        @else
                                            <pre class="text-[0.65rem] text-text-muted font-mono leading-relaxed">{{ json_encode(['De' => $activity->old_values, 'A' => $activity->new_values], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ─── Modal de Rechazo (RF-REQ-09) ─── --}}
    @include('livewire.requisitions._reject-modal')

    {{-- ─── Diálogo de confirmación global ─── --}}
    <x-confirm-modal />
</div>
