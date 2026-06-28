<div>
    {{-- Drawer de Detalle Rápido --}}
    <x-drawer show="showDetailDrawer" title="Detalles del Proyecto" maxWidth="lg">
        @if($detailProject)
            <div class="space-y-6">
                {{-- Resumen principal --}}
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-h3 text-text-primary pr-4">{{ $detailProject->name }}</h3>
                        <div class="flex items-center gap-1.5 mt-1 text-small text-text-muted">
                            <x-lucide-briefcase class="w-3.5 h-3.5 text-text-muted/70" />
                            <span>{{ $detailProject->client?->name ?? 'Sin cliente registrado' }}</span>
                        </div>
                    </div>
                    <x-status-badge class="shrink-0" :status="$detailProject->status" :map="['activo' => 'success', 'en_pausa' => 'warning', 'completado' => 'primary', 'cancelado' => 'danger']" />
                </div>

                {{-- Presupuesto y Gastos --}}
                <div class="bg-surface-main/50 p-4 rounded-xl">
                    <div class="flex justify-between items-end mb-3">
                        <div>
                            <p class="text-text-muted text-xs font-medium mb-0.5">Presupuesto</p>
                            <p class="font-bold text-h2 text-text-primary">${{ number_format($detailProject->budget, 2) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-text-muted text-xs font-medium mb-0.5">Gastado</p>
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

                <div class="grid grid-cols-2 gap-4 bg-surface-main/50 p-4 rounded-xl">
                    <x-data-label label="Fecha de Inicio">
                        <div class="flex items-center gap-1.5">
                            <x-lucide-calendar class="w-3.5 h-3.5 text-text-muted/70" />
                            <span>{{ $detailProject->start_date?->format('d/m/Y') ?? 'No definida' }}</span>
                        </div>
                    </x-data-label>
                    <x-data-label label="Fecha Estimada de Término">
                        <div class="flex items-center gap-1.5">
                            <x-lucide-calendar-clock class="w-3.5 h-3.5 text-text-muted/70" />
                            <span>{{ $detailProject->end_date?->format('d/m/Y') ?? 'No definida' }}</span>
                        </div>
                    </x-data-label>
                    <div class="col-span-2">
                        <x-data-label label="Descripción">
                            <div class="flex items-start gap-1.5">
                                <x-lucide-align-left class="w-3.5 h-3.5 text-text-muted/70 mt-0.5 shrink-0" />
                                <span>{{ $detailProject->description ?: 'Sin descripción' }}</span>
                            </div>
                        </x-data-label>
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
            <div class="space-y-6">
                <div class="flex justify-between items-start">
                    <div>
                        <x-skeleton class="h-6 w-48 mb-2" />
                        <x-skeleton class="h-4 w-32" />
                    </div>
                    <x-skeleton class="h-6 w-20 rounded-md" />
                </div>
                
                <x-skeleton class="h-24 w-full rounded-xl" />
                
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <x-skeleton class="h-3 w-20 mb-1" />
                        <x-skeleton class="h-4 w-32" />
                    </div>
                    <div>
                        <x-skeleton class="h-3 w-20 mb-1" />
                        <x-skeleton class="h-4 w-32" />
                    </div>
                    <div class="col-span-2">
                        <x-skeleton class="h-3 w-20 mb-1" />
                        <x-skeleton class="h-4 w-full mb-1" />
                        <x-skeleton class="h-4 w-4/5" />
                    </div>
                </div>
            </div>
        @endif
    </x-drawer>
</div>
