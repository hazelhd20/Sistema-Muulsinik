<div>
    {{-- ── Header ──────────────────────────────────────────── --}}
    <x-page-header
        subtitle="Proyectos"
        title="{{ $project->name }}"
    >
        <x-slot:actions>
            <x-button href="{{ route('proyectos.index') }}" variant="secondary" icon="arrow-left" wire:navigate>
                Volver
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-5">

        {{-- ── Info Card ───────────────────────────────────────── --}}
        <div class="card p-6">
            <div class="flex items-start justify-between flex-wrap gap-4 mb-6">
                <div>
                    <h2 class="text-h2 font-semibold text-text-primary">{{ $project->name }}</h2>
                    @if($project->client)
                        <p class="text-small text-text-muted mt-1 flex items-center gap-1.5">
                            <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
                            {{ $project->client }}
                        </p>
                    @endif
                </div>
                <x-status-badge
                    :status="$project->status"
                    :map="[
                        'activo'     => 'success',
                        'en_pausa'  => 'warning',
                        'completado' => 'secondary',
                        'cancelado'  => 'danger',
                    ]"
                />
            </div>

            {{-- Metadata grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-4">
                <div>
                    <span class="text-xs-fluid text-text-muted block mb-1">Presupuesto</span>
                    <span class="text-body font-semibold text-text-primary tabular-nums">
                        ${{ number_format($project->budget, 0, '.', ',') }}
                    </span>
                </div>
                <div>
                    <span class="text-xs-fluid text-text-muted block mb-1">Gasto acumulado</span>
                    <span class="text-body font-semibold text-text-primary tabular-nums">
                        ${{ number_format($project->total_expenses, 0, '.', ',') }}
                    </span>
                </div>
                <div>
                    <span class="text-xs-fluid text-text-muted block mb-1">Fecha de inicio</span>
                    <span class="text-body text-text-primary">
                        {{ $project->start_date?->format('d/m/Y') ?? '—' }}
                    </span>
                </div>
                <div>
                    <span class="text-xs-fluid text-text-muted block mb-1">Fecha de fin</span>
                    <span class="text-body text-text-primary">
                        {{ $project->end_date?->format('d/m/Y') ?? '—' }}
                    </span>
                </div>
                @if($project->description)
                    <div class="col-span-2 md:col-span-4">
                        <span class="text-xs-fluid text-text-muted block mb-1">Descripción</span>
                        <span class="text-small text-text-secondary">{{ $project->description }}</span>
                    </div>
                @endif
            </div>

            {{-- Budget progress bar --}}
            @php
                $percent  = min($project->budget_used_percent, 100);
                $barColor = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-primary-600');
            @endphp
            <div class="mt-5 pt-5 border-t border-border">
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
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                        El gasto acumulado supera el presupuesto asignado.
                    </p>
                @endif
            </div>
        </div>

        {{-- ── Summary Stats ─────────────────────────────────── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary-50">
                    <i data-lucide="file-text" class="w-[18px] h-[18px] text-primary-600"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-h2 font-bold text-text-primary leading-none tabular-nums">
                        {{ $project->requisitions->count() }}
                    </p>
                    <p class="text-xs-fluid text-text-muted mt-0.5">Requisiciones</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-success-light">
                    <i data-lucide="check-circle" class="w-[18px] h-[18px] text-success"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-h2 font-bold text-text-primary leading-none tabular-nums">
                        {{ $project->requisitions->where('status', 'aprobada')->count() }}
                    </p>
                    <p class="text-xs-fluid text-text-muted mt-0.5">Aprobadas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-warning-light">
                    <i data-lucide="clock" class="w-[18px] h-[18px] text-warning"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-h2 font-bold text-text-primary leading-none tabular-nums">
                        {{ $project->requisitions->where('status', 'pendiente')->count() }}
                    </p>
                    <p class="text-xs-fluid text-text-muted mt-0.5">Pendientes</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-danger-light">
                    <i data-lucide="receipt" class="w-[18px] h-[18px] text-danger"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-h2 font-bold text-text-primary leading-none tabular-nums">
                        {{ $project->expenses->count() }}
                    </p>
                    <p class="text-xs-fluid text-text-muted mt-0.5">Gastos directos</p>
                </div>
            </div>
        </div>

        {{-- ── Requisitions Table ──────────────────────────────── --}}
        <div class="card p-0 overflow-hidden">
            <div class="px-6 py-4 flex items-center gap-2 border-b border-border">
                <h2 class="text-h2 text-text-primary">Requisiciones</h2>
                <span class="badge badge-secondary">{{ $project->requisitions->count() }}</span>
            </div>

            <div class="table-embedded border-t-0 border-x-0 rounded-none">
                <table>
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Proveedor</th>
                            <th>Solicitante</th>
                            <th>Fecha</th>
                            <th class="text-right">Total</th>
                            <th>Estado</th>
                            <th class="text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($project->requisitions as $requisition)
                            <tr class="hover:bg-surface-hover/30">
                                <td class="font-medium text-small text-text-primary tabular-nums">
                                    {{ $requisition->number ?? 'REQ-' . str_pad($requisition->id, 5, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="text-small text-text-secondary">
                                    {{ $requisition->vendor?->name ?? '—' }}
                                </td>
                                <td class="text-small text-text-secondary">
                                    {{ $requisition->creator?->name ?? '—' }}
                                </td>
                                <td class="text-small text-text-muted tabular-nums">
                                    {{ $requisition->date?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="text-right font-semibold text-small text-text-primary tabular-nums">
                                    ${{ number_format($requisition->total, 2) }}
                                </td>
                                <td>
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
                                <td class="text-right">
                                    <x-button href="{{ route('requisiciones.show', $requisition->id) }}"
                                       variant="icon" icon="eye"
                                       title="Ver detalle"
                                       aria-label="Ver detalle de {{ $requisition->number ?? $requisition->id }}"
                                       wire:navigate />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="flex flex-col items-center justify-center py-10 gap-2">
                                        <div class="w-10 h-10 rounded-full bg-surface-main flex items-center justify-center">
                                            <i data-lucide="file-text" class="w-5 h-5 text-text-muted"></i>
                                        </div>
                                        <p class="text-small text-text-muted">Sin requisiciones registradas</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Expenses Table ──────────────────────────────────── --}}
        <div class="card p-0 overflow-hidden">
            <div class="px-6 py-4 flex items-center gap-2 border-b border-border">
                <h2 class="text-h2 text-text-primary">Gastos Directos</h2>
                <span class="badge badge-secondary">{{ $project->expenses->count() }}</span>
            </div>

            <div class="table-embedded border-t-0 border-x-0 rounded-none">
                <table>
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th>Categoría</th>
                            <th>Registrado por</th>
                            <th>Fecha</th>
                            <th class="text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($project->expenses as $expense)
                            <tr class="hover:bg-surface-hover/30">
                                <td class="font-medium text-small text-text-primary">
                                    {{ $expense->concept }}
                                </td>
                                <td class="text-small text-text-muted">
                                    {{ $expense->category ?? '—' }}
                                </td>
                                <td class="text-small text-text-secondary">
                                    {{ $expense->user?->name ?? '—' }}
                                </td>
                                <td class="text-small text-text-muted tabular-nums">
                                    {{ $expense->date?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="text-right font-semibold text-small text-text-primary tabular-nums">
                                    ${{ number_format($expense->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="flex flex-col items-center justify-center py-10 gap-2">
                                        <div class="w-10 h-10 rounded-full bg-surface-main flex items-center justify-center">
                                            <i data-lucide="receipt" class="w-5 h-5 text-text-muted"></i>
                                        </div>
                                        <p class="text-small text-text-muted">Sin gastos directos registrados</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
