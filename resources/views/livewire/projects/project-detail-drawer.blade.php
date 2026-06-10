<div>
    {{-- Drawer de Detalle Rápido --}}
    <x-drawer show="showDetailDrawer" title="Detalles del Proyecto" maxWidth="lg">
        @if($detailProject)
            <div class="space-y-6">
                {{-- Resumen principal --}}
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-h3 text-text-primary">{{ $detailProject->name }}</h3>
                        <p class="text-sm text-text-muted mt-1">Cliente: {{ $detailProject->client ?? 'Sin cliente' }}</p>
                    </div>
                    <x-status-badge :status="$detailProject->status" :map="['activo' => 'success', 'en_pausa' => 'warning', 'completado' => 'primary', 'cancelado' => 'danger']" />
                </div>

                {{-- Presupuesto y Gastos --}}
                <div class="bg-surface-main/30 p-4 rounded-xl border border-border">
                    <div class="flex justify-between items-end mb-3">
                        <div>
                            <p class="text-text-muted text-xs-fluid font-medium mb-0.5">Presupuesto</p>
                            <p class="font-bold text-lg text-text-primary">${{ number_format($detailProject->budget, 2) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-text-muted text-xs-fluid font-medium mb-0.5">Gastado</p>
                            <p class="font-semibold text-text-primary">${{ number_format($detailProject->total_expenses, 2) }}</p>
                        </div>
                    </div>
                    <div class="w-full h-2 bg-surface-main rounded-full overflow-hidden mb-1">
                        @php
                            $percent = min($detailProject->budget_used_percent, 100);
                            $barColor = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-primary-600');
                        @endphp
                        <div class="{{ $barColor }} h-full rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                    </div>
                    <div class="text-right text-xs text-text-muted">
                        {{ $detailProject->budget_used_percent }}% utilizado
                    </div>
                </div>

                {{-- Detalles en grid --}}
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-text-muted text-xs-fluid font-medium mb-0.5">Fecha de Inicio</p>
                        <p class="font-medium text-text-primary">{{ $detailProject->start_date?->format('d/m/Y') ?? 'No definida' }}</p>
                    </div>
                    <div>
                        <p class="text-text-muted text-xs-fluid font-medium mb-0.5">Fecha Estimada de Término</p>
                        <p class="font-medium text-text-primary">{{ $detailProject->end_date?->format('d/m/Y') ?? 'No definida' }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-text-muted text-xs-fluid font-medium mb-0.5">Descripción</p>
                        <p class="font-medium text-text-primary">{{ $detailProject->description ?: 'Sin descripción' }}</p>
                    </div>
                </div>

                {{-- Acciones del Drawer --}}
                <div class="flex justify-end gap-3 pt-6 border-t border-border mt-auto">
                    <x-button as="a" href="{{ route('proyectos.show', $detailProject->id) }}" variant="secondary" wire:navigate>
                        Ver Ficha Completa
                    </x-button>
                    <x-button wire:click="$dispatch('edit-project', { id: {{ $detailProject->id }} }); showDetailDrawer = false" variant="primary" icon="pencil">
                        Editar Proyecto
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
