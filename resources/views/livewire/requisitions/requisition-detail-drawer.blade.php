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
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-h3 text-text-primary pr-4">
                            {{ $detailRequisition->number ?? 'REQ-' . str_pad($detailRequisition->id, 5, '0', STR_PAD_LEFT) }}
                        </h3>
                        <div class="flex items-center gap-1.5 mt-1 text-small text-text-muted">
                            <x-lucide-calendar class="w-3.5 h-3.5 text-text-muted/70" />
                            <span>Creada el {{ $detailRequisition->date?->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</span>
                        </div>
                    </div>
                    <x-status-badge
                        :status="$detailRequisition->status"
                        :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
                </div>

                {{-- Detalles en grid --}}
                <div class="grid grid-cols-2 gap-4 bg-surface-main/50 p-4 rounded-xl">
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
                    
                    <div class="col-span-2">
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
                        <div class="col-span-2">
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
                <div class="mt-8 mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider">
                            Productos
                        </h4>
                        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-surface-main border border-border/60 text-xs font-medium text-text-muted">
                            <x-lucide-package class="w-3.5 h-3.5" />
                            {{ $detailRequisition->items->count() }} {{ $detailRequisition->items->count() === 1 ? 'artículo' : 'artículos' }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-3">
                        @foreach($detailRequisition->items as $item)
                            <div class="flex justify-between items-center bg-surface-main/50 rounded-xl p-3.5">
                                <div>
                                    <p class="font-medium text-small text-text-primary leading-snug mb-1">
                                        {{ $item->product?->canonical_name ?? 'Producto desconocido' }}
                                    </p>
                                    <p class="text-xs font-medium text-text-muted flex items-center mt-0.5">
                                        {{ number_format($item->quantity, 2) }}
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-surface-hover text-text-secondary border border-border text-[9px] font-bold uppercase tracking-wider ml-1.5">
                                            {{ $item->product?->measure?->code ?? $item->measure?->abbreviation ?? 'pza' }}
                                        </span>
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
                <div class="pt-4 mt-4 border-t border-border/40">
                    <div class="flex justify-between items-center text-small mb-2">
                        <span class="text-text-muted">Subtotal</span>
                        <span class="font-medium text-text-primary tabular-nums">${{ number_format($detailRequisition->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-small mb-4">
                        <span class="text-text-muted">IVA (16%)</span>
                        <span class="font-medium text-text-primary tabular-nums">${{ number_format($detailRequisition->tax_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-4 border-t border-border">
                        <span class="text-small font-bold text-text-primary">Total Final</span>
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
                <x-button as="a" href="{{ route('requisiciones.show', $detailRequisition->id) }}" variant="soft" wire:navigate>
                    Ver Ficha Completa
                </x-button>

                @if($detailRequisition->status === 'pendiente' && (auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*')))
                    <x-button wire:click="openRejectModal" variant="soft" icon="x-circle">
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
