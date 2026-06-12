<div>
    {{-- Drawer de Detalle Rápido --}}
    <x-drawer show="showDetailDrawer" title="Detalles de Producto" maxWidth="md">
        @if($detailProduct)
            <div class="space-y-6">
                {{-- Resumen principal --}}
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-h3 text-text-primary">{{ $detailProduct->canonical_name }}</h3>
                        <p class="text-small text-text-secondary mt-1">Registrado el {{ $detailProduct->created_at?->format('d/m/Y') }}</p>
                    </div>
                </div>

                {{-- Detalles en grid --}}
                <div class="grid grid-cols-2 gap-4 text-small bg-surface-main/30 p-4 rounded-xl border border-border">
                    <div>
                        <p class="text-text-muted text-xs font-medium mb-0.5">Categoría</p>
                        <p class="font-medium text-text-primary">
                            @if($detailProduct->category)
                                <x-dynamic-badge :value="$detailProduct->category->name" />
                            @else
                                <span class="text-text-muted">—</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-text-muted text-xs font-medium mb-0.5">Unidad</p>
                        <p class="font-medium text-text-primary">
                            @if($detailProduct->measure && $detailProduct->measure->abbreviation)
                                <x-badge variant="secondary">{{ $detailProduct->measure->abbreviation }}</x-badge>
                            @else
                                <span class="text-text-muted">—</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-text-muted text-xs font-medium mb-0.5">Descripción Técnica</p>
                        <p class="font-medium text-text-primary">{{ $detailProduct->description ?: 'Sin descripción' }}</p>
                    </div>
                </div>

                {{-- Acciones del Drawer --}}
                <div class="flex justify-end gap-3 pt-6 border-t border-border mt-auto">
                    <x-button wire:click="$dispatch('edit-product', { id: {{ $detailProduct->id }} }); showDetailDrawer = false" variant="primary" icon="pencil">
                        Editar Producto
                    </x-button>
                </div>
            </div>
        @else
            <div class="flex items-center justify-center h-48">
                <div class="w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
            </div>
        @endif
    </x-drawer>
</div>
