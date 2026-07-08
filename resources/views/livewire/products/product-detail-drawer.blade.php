<div>
    {{-- Drawer de Detalle Rápido --}}
    <x-drawer show="showDetailDrawer" title="Detalles de Producto" maxWidth="md">
        @if($detailProduct)
            <div class="space-y-6">
                {{-- Resumen principal --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-h2 font-bold text-text-primary truncate pr-2">{{ $detailProduct->canonical_name }}</h3>
                        <div class="flex items-center gap-1.5 mt-1 text-small text-text-muted">
                            <x-lucide-calendar class="w-3.5 h-3.5 text-text-muted/70 shrink-0" />
                            <span class="truncate">Registrado el {{ $detailProduct->created_at?->format('d/m/Y') ?? '—' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Detalles en grid --}}
                <div class="grid grid-cols-2 gap-4 bg-surface-main/50 p-4 rounded-xl min-w-0 max-w-full">
                    <x-data-label label="Categoría">
                        @if($detailProduct->category)
                            <x-dynamic-badge :value="$detailProduct->category->name" size="sm" />
                        @else
                            <span class="text-text-muted">—</span>
                        @endif
                    </x-data-label>
                    <x-data-label label="Unidad">
                        @if($detailProduct->measure && $detailProduct->measure->abbreviation)
                            <x-badge variant="secondary" size="sm" :normalCase="true">{{ $detailProduct->measure->abbreviation }}</x-badge>
                        @else
                            <span class="text-text-muted">—</span>
                        @endif
                    </x-data-label>
                    <div class="col-span-2 min-w-0">
                        <x-data-label label="Descripción Técnica">
                            <div class="flex items-start gap-1.5 min-w-0">
                                <x-lucide-align-left class="w-3.5 h-3.5 text-text-muted/70 mt-0.5 shrink-0" />
                                <span class="break-words min-w-0 flex-1">{{ $detailProduct->description ?: 'Sin descripción' }}</span>
                            </div>
                        </x-data-label>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 w-full">
                    <x-button wire:click="$dispatch('edit-product', { id: {{ $detailProduct->id }} }); showDetailDrawer = false" variant="primary" icon="pencil" class="w-full sm:w-auto justify-center">
                        Editar Producto
                    </x-button>
                </div>
            </x-slot:footer>
        @else
            <div class="space-y-6">
                <div class="flex justify-between items-start">
                    <div>
                        <x-skeleton class="h-6 w-48 mb-2" />
                        <x-skeleton class="h-4 w-32" />
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-skeleton class="h-3 w-20 mb-1" />
                        <x-skeleton class="h-6 w-24" />
                    </div>
                    <div>
                        <x-skeleton class="h-3 w-20 mb-1" />
                        <x-skeleton class="h-6 w-16" />
                    </div>
                    <div class="col-span-2">
                        <x-skeleton class="h-3 w-28 mb-1" />
                        <x-skeleton class="h-4 w-full mb-1" />
                        <x-skeleton class="h-4 w-4/5" />
                    </div>
                </div>
            </div>
        @endif
    </x-drawer>
</div>
