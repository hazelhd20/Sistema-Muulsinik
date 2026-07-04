<div>
    {{-- ── Header ──────────────────────────────────────────── --}}
    @php
        $breadcrumbs = [
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Proyectos', 'url' => route('proyectos.index')],
            ['label' => $project->name]
        ];
    @endphp
    <x-page-header :breadcrumbs="$breadcrumbs" :status="$project->status ?? null">
        <x-slot:title>
            {{ $project->name }}
        </x-slot:title>
        <x-slot:descriptionSlot>
            <div class="flex items-center gap-1.5 mt-0.5">
                <x-lucide-briefcase class="w-4 h-4 text-text-muted/70" />
                <span>{{ $project->client?->name ?? 'Sin cliente registrado' }}</span>
            </div>
        </x-slot:descriptionSlot>
    </x-page-header>

    <div class="space-y-5">

        {{-- ── Info Card ───────────────────────────────────────── --}}
        <x-card class="mb-6">
            <x-card.header title="Detalles Generales" />
            <x-card.body>
                {{-- Metadata grid --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <x-data-label label="Presupuesto">
                        <div class="flex items-center gap-1.5">
                            <x-lucide-circle-dollar-sign class="w-3.5 h-3.5 text-text-muted/70" />
                            <span>{{ '$' . number_format($project->budget, 0, '.', ',') }}</span>
                        </div>
                    </x-data-label>
                    <x-data-label label="Gasto acumulado">
                        <div class="flex items-center gap-1.5">
                            <x-lucide-receipt class="w-3.5 h-3.5 text-text-muted/70" />
                            <span>{{ '$' . number_format($project->total_expenses, 0, '.', ',') }}</span>
                        </div>
                    </x-data-label>
                    <x-data-label label="Fecha de inicio">
                        <div class="flex items-center gap-1.5">
                            <x-lucide-calendar class="w-3.5 h-3.5 text-text-muted/70" />
                            <span>{{ $project->start_date?->format('d/m/Y') ?? '—' }}</span>
                        </div>
                    </x-data-label>
                    <x-data-label label="Fecha de fin">
                        <div class="flex items-center gap-1.5">
                            <x-lucide-calendar-clock class="w-3.5 h-3.5 text-text-muted/70" />
                            <span>{{ $project->end_date?->format('d/m/Y') ?? '—' }}</span>
                        </div>
                    </x-data-label>
                    
                    @if($project->description)
                        <div class="col-span-2 md:col-span-4">
                            <x-data-label label="Descripción">
                                <div class="flex items-start gap-1.5">
                                    <x-lucide-align-left class="w-3.5 h-3.5 text-text-muted/70 mt-0.5 shrink-0" />
                                    <span>{{ $project->description }}</span>
                                </div>
                            </x-data-label>
                        </div>
                    @endif
                </div>

                {{-- Budget progress bar --}}
                @php
                    $percent  = min($project->budget_used_percent, 100);
                    $barColor = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-primary-600');
                @endphp
                <div class="mt-6 pt-6 border-t border-border/40">
                    <div class="flex items-center justify-between text-xs-fluid mb-2">
                        <span class="text-text-muted">Ejecución del presupuesto</span>
                        <span class="font-semibold text-text-primary tabular-nums">{{ $project->budget_used_percent }}%</span>
                    </div>
                    <div class="w-full h-2 bg-surface-main rounded-full overflow-hidden">
                        <div class="{{ $barColor }} h-full rounded-full transition-all duration-500"
                             style="width: {{ $percent }}%">
                        </div>
                    </div>
                    @if($project->budget_used_percent > 100)
                        <p class="text-xs-fluid text-danger mt-1.5 flex items-center gap-1">
                            <x-lucide-alert-triangle class="w-3.5 h-3.5" />
                            El gasto acumulado supera el presupuesto asignado.
                        </p>
                    @endif
                </div>
            </x-card.body>
        </x-card>

        {{-- ── Summary Stats ─────────────────────────────────── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <x-stat-card 
                title="Requisiciones" 
                value="{{ $project->requisitions->count() }}" 
                icon="file-text" 
                color="primary" 
            />
            <x-stat-card 
                title="Aprobadas" 
                value="{{ $project->requisitions->where('status', 'aprobada')->count() }}" 
                icon="check-circle" 
                color="success" 
            />
            <x-stat-card 
                title="Pendientes" 
                value="{{ $project->requisitions->where('status', 'pendiente')->count() }}" 
                icon="clock" 
                color="warning" 
            />
            <x-stat-card 
                title="Gastos directos" 
                value="{{ $project->expenses->count() }}" 
                icon="receipt" 
                color="danger" 
            />
        </div>

        {{-- ── Requisitions Table ──────────────────────────────── --}}
        <x-card class="mb-6 overflow-hidden">
            <x-card.header title="Requisiciones">
                <x-slot:action>
                    @if($project->requisitions->count() > 0)
                        <x-badge variant="secondary" size="md" icon="file-text" :normal-case="true">
                            {{ $project->requisitions->count() }} {{ $project->requisitions->count() === 1 ? 'requisición' : 'requisiciones' }}
                        </x-badge>
                    @endif
                </x-slot:action>
            </x-card.header>

            {{-- Desktop Table --}}
            <div class="hidden md:block w-full overflow-x-auto">
                @if($project->requisitions->isNotEmpty())
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-surface-th border-b border-border/40 text-xs-fluid font-semibold text-text-muted uppercase tracking-wider">
                            <tr>
                                <th class="pl-6 pr-4 py-3 whitespace-nowrap">Número</th>
                                <th class="px-4 py-3 whitespace-nowrap">Proveedor</th>
                                <th class="px-4 py-3 whitespace-nowrap">Solicitante</th>
                                <th class="px-4 py-3 whitespace-nowrap">Fecha</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Total</th>
                                <th class="px-4 py-3 whitespace-nowrap">Estado</th>
                                <th class="pr-6 pl-4 py-3 text-right whitespace-nowrap">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/40 border-b border-border/40">
                            @foreach($project->requisitions as $requisition)
                                <tr class="hover:bg-surface-hover transition-colors duration-150">
                                    <td class="pl-6 pr-4 py-3 font-medium text-body text-text-primary">
                                        {{ $requisition->number ?? 'REQ-' . str_pad($requisition->id, 5, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="px-4 py-3 text-body text-text-muted">
                                        {{ $requisition->supplier_name }}
                                    </td>
                                    <td class="px-4 py-3 text-body text-text-secondary">
                                        {{ $requisition->creator?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-body text-text-muted tabular-nums">
                                        {{ $requisition->date?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-body text-text-primary tabular-nums">
                                        ${{ number_format($requisition->total, 2) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-status-badge
                                            :status="$requisition->status"
                                            :map="[
                                                'borrador'  => 'secondary',
                                                'pendiente' => 'warning',
                                                'aprobada'  => 'success',
                                                'rechazada' => 'danger',
                                            ]"
                                        />
                                    </td>
                                    <td class="pr-6 pl-4 py-3 text-right">
                                        <x-button href="{{ route('requisiciones.show', $requisition->id) }}"
                                           variant="icon" icon="eye"
                                           title="Ver detalle"
                                           aria-label="Ver detalle de {{ $requisition->number ?? $requisition->id }}"
                                           wire:navigate />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="py-12 px-6 flex justify-center">
                        <x-empty-state icon="file-text" title="Sin requisiciones registradas" />
                    </div>
                @endif
            </div>

            {{-- Mobile Cards (Clean Row Pattern) --}}
            <div class="md:hidden divide-y divide-border/60 border-t border-border/40">
                @if($project->requisitions->isNotEmpty())
                    @foreach($project->requisitions as $requisition)
                        <div class="py-3 px-4 sm:py-3.5 sm:px-6 flex flex-col gap-2 hover:bg-surface-hover/50 transition-colors duration-150">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-body text-text-primary">
                                            {{ $requisition->number ?? 'REQ-' . str_pad($requisition->id, 5, '0', STR_PAD_LEFT) }}
                                        </span>
                                        <x-status-badge
                                            :status="$requisition->status"
                                            size="sm"
                                            :map="[
                                                'borrador'  => 'secondary',
                                                'pendiente' => 'warning',
                                                'aprobada'  => 'success',
                                                'rechazada' => 'danger',
                                            ]"
                                        />
                                    </div>
                                    <p class="text-small text-text-secondary font-medium truncate mt-0.5">
                                        {{ $requisition->supplier_name }}
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-bold text-body text-text-primary tabular-nums">
                                        ${{ number_format($requisition->total, 2) }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-1 border-t border-border/40 text-xs-fluid text-text-muted">
                                <span class="flex items-center gap-1">
                                    <x-lucide-user class="w-3.5 h-3.5 opacity-70" />
                                    <span>{{ $requisition->creator?->name ?? '—' }}</span>
                                </span>
                                <div class="flex items-center gap-3">
                                    <span>{{ $requisition->date?->format('d/m/Y') ?? '—' }}</span>
                                    <x-button href="{{ route('requisiciones.show', $requisition->id) }}"
                                        variant="icon" icon="eye" size="sm"
                                        title="Ver detalle"
                                        aria-label="Ver detalle de {{ $requisition->number ?? $requisition->id }}"
                                        wire:navigate />
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="py-12 px-6 flex justify-center">
                        <x-empty-state icon="file-text" title="Sin requisiciones registradas" />
                    </div>
                @endif
            </div>
        </x-card>

        {{-- ── Expenses Table ──────────────────────────────────── --}}
        <x-card class="mb-6 overflow-hidden">
            <x-card.header title="Gastos Directos">
                <x-slot:action>
                    @if($project->expenses->count() > 0)
                        <x-badge variant="secondary" size="md" icon="receipt" :normal-case="true">
                            {{ $project->expenses->count() }} {{ $project->expenses->count() === 1 ? 'gasto' : 'gastos' }}
                        </x-badge>
                    @endif
                </x-slot:action>
            </x-card.header>

            {{-- Desktop Table --}}
            <div class="hidden md:block w-full overflow-x-auto">
                @if($project->expenses->isNotEmpty())
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-surface-th border-b border-border/40 text-xs-fluid font-semibold text-text-muted uppercase tracking-wider">
                            <tr>
                                <th class="pl-6 pr-4 py-3 whitespace-nowrap">Concepto</th>
                                <th class="px-4 py-3 whitespace-nowrap">Categoría</th>
                                <th class="px-4 py-3 whitespace-nowrap">Registrado por</th>
                                <th class="px-4 py-3 whitespace-nowrap">Fecha</th>
                                <th class="pr-6 pl-4 py-3 text-right whitespace-nowrap">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/40 border-b border-border/40">
                            @foreach($project->expenses as $expense)
                                <tr class="hover:bg-surface-hover transition-colors duration-150">
                                    <td class="pl-6 pr-4 py-3 font-medium text-body text-text-primary">
                                        {{ $expense->concept }}
                                    </td>
                                    <td class="px-4 py-3 text-body">
                                        <x-dynamic-badge :value="$expense->category_name" />
                                    </td>
                                    <td class="px-4 py-3 text-body text-text-secondary">
                                        {{ $expense->user?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-body text-text-muted tabular-nums">
                                        {{ $expense->date?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td class="pr-6 pl-4 py-3 text-right font-semibold text-body text-text-primary tabular-nums">
                                        ${{ number_format($expense->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="py-12 px-6 flex justify-center">
                        <x-empty-state icon="receipt" title="Sin gastos directos registrados" />
                    </div>
                @endif
            </div>

            {{-- Mobile Cards (Clean Row Pattern) --}}
            <div class="md:hidden divide-y divide-border/60 border-t border-border/40">
                @if($project->expenses->isNotEmpty())
                    @foreach($project->expenses as $expense)
                        <div class="py-3 px-4 sm:py-3.5 sm:px-6 flex flex-col gap-2 hover:bg-surface-hover/50 transition-colors duration-150">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-body text-text-primary leading-snug break-words">
                                        {{ $expense->concept }}
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-bold text-body text-text-primary tabular-nums">
                                        ${{ number_format($expense->amount, 2) }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-1 border-t border-border/40 text-xs-fluid text-text-muted">
                                <div class="flex items-center gap-2">
                                    <x-dynamic-badge :value="$expense->category_name" size="xs" />
                                    <span class="flex items-center gap-1">
                                        <x-lucide-user class="w-3 h-3 opacity-70" />
                                        <span>{{ $expense->user?->name ?? '—' }}</span>
                                    </span>
                                </div>
                                <span class="shrink-0">{{ $expense->date?->format('d/m/Y') ?? '—' }}</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="py-12 px-6 flex justify-center">
                        <x-empty-state icon="receipt" title="Sin gastos directos registrados" />
                    </div>
                @endif
            </div>
        </x-card>

    </div>
</div>
