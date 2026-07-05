<div>
    {{-- Drawer de Detalle Rápido --}}
    <x-drawer show="showDetailDrawer" title="Detalles de Requisición" maxWidth="xl" x-on:open-requisition-detail.window="$wire.set('showDetailDrawer', true)">
        {{-- Skeleton Loading --}}
        <div wire:loading wire:target="showDetail" class="space-y-6">
            <div class="flex items-start justify-between">
                <div>
                    <x-skeleton class="h-6 w-48 mb-2" />
                    <x-skeleton class="h-4 w-32" />
                </div>
                <x-skeleton class="h-6 w-20 rounded-md shrink-0" />
            </div>
            
            <x-skeleton class="h-28 w-full rounded-xl" />
            
            <div class="space-y-3">
                <x-skeleton class="h-4 w-24 mb-2" />
                <x-skeleton class="h-14 w-full rounded-xl" />
                <x-skeleton class="h-14 w-full rounded-xl" />
            </div>
        </div>

        <div wire:loading.remove wire:target="showDetail">
            @if($detailRequisition)
                <div class="space-y-6">
                    {{-- Resumen principal --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-h2 font-bold text-text-primary truncate pr-2">
                            {{ $detailRequisition->number ?? 'REQ-' . str_pad($detailRequisition->id, 5, '0', STR_PAD_LEFT) }}
                        </h3>
                        <div class="flex items-center gap-1.5 mt-1 text-small text-text-muted">
                            <x-lucide-calendar class="w-3.5 h-3.5 text-text-muted/70 shrink-0" />
                            <span class="truncate">Creada el {{ $detailRequisition->date?->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</span>
                        </div>
                    </div>
                    <div class="shrink-0 self-start">
                        <x-status-badge
                            size="sm"
                            :status="$detailRequisition->status"
                            :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
                    </div>
                </div>

                {{-- Detalles en grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-surface-main/50 p-4 rounded-xl">
                    <x-data-label label="Proyecto">
                        <div class="flex items-center gap-1.5">
                            <x-lucide-hard-hat class="w-3.5 h-3.5 text-text-muted/70" />
                            <span>{{ $detailRequisition->project?->name ?? '—' }}</span>
                        </div>
                    </x-data-label>
                    <x-data-label label="Solicitante">
                        <div class="flex items-center gap-1.5">
                            <x-lucide-user class="w-3.5 h-3.5 text-text-muted/70" />
                            <span>{{ $detailRequisition->creator?->name ?? '—' }}</span>
                        </div>
                    </x-data-label>
                    
                    <div class="col-span-1 sm:col-span-2">
                        @php
                            $proveedorName = $detailRequisition->vendor?->supplier?->trade_name 
                                ?? $detailRequisition->vendor?->name 
                                ?? $detailRequisition->items->first()?->supplier?->trade_name 
                                ?? '—';
                        @endphp
                        <x-data-label label="Proveedor Seleccionado">
                            <div class="flex items-center gap-1.5">
                                <x-lucide-truck class="w-3.5 h-3.5 text-text-muted/70" />
                                <span>{{ $proveedorName }}</span>
                            </div>
                        </x-data-label>
                    </div>

                    @if($detailRequisition->approver && in_array($detailRequisition->status, ['aprobada', 'rechazada']))
                        <div class="col-span-1 sm:col-span-2">
                            <x-data-label label="{{ $detailRequisition->status === 'aprobada' ? 'Aprobada por' : 'Rechazada por' }}">
                                <div class="flex items-center gap-1.5">
                                    <x-dynamic-component :component="$detailRequisition->status === 'aprobada' ? 'lucide-user-check' : 'lucide-user-x'" class="w-3.5 h-3.5 text-text-muted/70" />
                                    <span>{{ $detailRequisition->approver->name }}</span>
                                </div>
                            </x-data-label>
                        </div>
                    @endif
                </div>

                {{-- Banner de rechazo --}}
                @if($detailRequisition->status === 'rechazada' && $detailRequisition->rejection_comment)
                    <x-alert variant="danger" icon="x-octagon" title="Motivo del rechazo" class="mt-4">
                        {{ $detailRequisition->rejection_comment }}
                    </x-alert>
                @endif

                {{-- Partidas --}}
                <div class="mt-6">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-xs-fluid font-semibold text-text-muted uppercase tracking-wider">
                            Productos
                        </h4>
                        <x-badge variant="secondary" size="sm" icon="package" :normal-case="true">
                            {{ $detailRequisition->items->count() }} {{ $detailRequisition->items->count() === 1 ? 'artículo' : 'artículos' }}
                        </x-badge>
                    </div>
                    <div class="divide-y divide-border/60 border-t border-b border-border/60">
                        @foreach($detailRequisition->items as $index => $item)
                            <div class="py-2.5 flex flex-col gap-1.5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-body text-text-primary leading-snug break-words">
                                            {{ $item->product?->canonical_name ?? 'Producto desconocido' }}
                                        </p>
                                    </div>
                                    @if($item->product?->category?->name)
                                        <div class="shrink-0 pt-0.5">
                                            <x-dynamic-badge :value="$item->product->category->name" size="sm" />
                                        </div>
                                    @endif
                                </div>
                                <div class="flex items-baseline justify-between gap-2 pt-0.5">
                                    <div class="text-small text-text-secondary font-medium">
                                        <span>{{ number_format($item->quantity, 2) }}</span>
                                        <span class="uppercase ml-0.5">{{ $item->product?->measure?->abbreviation ?? $item->measure?->abbreviation ?? 'pza' }}</span>
                                        @if($item->unit_price > 0)
                                            <span class="text-text-muted mx-1">×</span>
                                            <span>${{ number_format($item->unit_price, 2) }}</span>
                                        @endif
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="font-bold text-body text-text-primary tabular-nums">
                                            ${{ number_format($item->line_subtotal_computed ?? $item->line_total_computed ?? 0, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Desglose contable rápido --}}
                <div class="pt-3.5 flex flex-col gap-2 text-small text-text-secondary">
                    <div class="flex justify-between items-center">
                        <span>Subtotal s/IVA</span>
                        <span class="font-medium text-text-primary tabular-nums">${{ number_format($detailRequisition->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>IVA (16%)</span>
                        <span class="font-medium text-text-primary tabular-nums">${{ number_format($detailRequisition->tax_amount, 2) }}</span>
                    </div>
                </div>

                </div>
            @endif
        </div>
        
        @if($detailRequisition)
        <x-slot:footer>
            <div class="flex flex-col gap-3 w-full" wire:loading.remove wire:target="showDetail">
                {{-- Sticky Total Banner --}}
                <div class="flex items-center justify-between pb-1">
                    <div class="flex flex-col">
                        <span class="text-xs-fluid font-bold text-text-muted uppercase tracking-wider">Importe Total</span>
                        <span class="text-small text-text-secondary">IVA (16%) incluido</span>
                    </div>
                    <span class="text-h1 font-bold text-text-primary tabular-nums tracking-tight">
                        ${{ number_format($detailRequisition->total, 2) }}
                    </span>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 w-full">
                    <x-button as="a" href="{{ route('requisiciones.show', $detailRequisition->id) }}" variant="secondary" class="w-full sm:w-auto justify-center" wire:navigate>
                        Ver Ficha Completa
                    </x-button>

                    @if($detailRequisition->status === 'pendiente' && (auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*')))
                        <div class="grid grid-cols-2 gap-2 w-full sm:w-auto sm:flex sm:items-center">
                            <x-button wire:click="openRejectModal" variant="secondary" icon="x-circle" class="w-full sm:w-auto justify-center">
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
                                variant="success" icon="check-circle" class="w-full sm:w-auto justify-center">
                                Aprobar
                            </x-button>
                        </div>
                    @endif
                </div>
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
