 <div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Reportes y Analítica</h1>
            <p class="text-sm text-text-muted">Análisis financiero y operativo del sistema</p>
        </div>
        <div class="flex items-center gap-3">
            <select wire:model.live="projectFilter" class="input w-auto min-w-[180px]">
                <option value="">Todos los proyectos</option>
                @foreach($projects as $proj)
                    <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="period" class="input w-auto min-w-[140px]">
                <option value="week">Última semana</option>
                <option value="month">Último mes</option>
                <option value="quarter">Último trimestre</option>
                <option value="year">Último año</option>
                <option value="all">Todo</option>
            </select>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-primary-100">
                <i data-lucide="dollar-sign" class="w-5 h-5 text-primary-600"></i>
            </div>
            <div>
                <p class="text-xl font-bold text-text-primary">${{ number_format($totalExpenses, 0, '.', ',') }}</p>
                <p class="text-xs text-text-muted">Gasto total del período</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-green-100">
                <i data-lucide="receipt" class="w-5 h-5 text-green-600"></i>
            </div>
            <div>
                <p class="text-xl font-bold text-text-primary">{{ $expenseCount }}</p>
                <p class="text-xs text-text-muted">Transacciones · ${{ number_format($avgExpense, 0, '.', ',') }} prom.</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber-100">
                <i data-lucide="check-circle" class="w-5 h-5 text-amber-600"></i>
            </div>
            <div>
                <p class="text-xl font-bold text-text-primary">{{ $requisitionsApproved }}</p>
                <p class="text-xs text-text-muted">Requisiciones aprobadas</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-sky-100">
                <i data-lucide="hard-hat" class="w-5 h-5 text-sky-600"></i>
            </div>
            <div>
                <p class="text-xl font-bold text-text-primary">{{ $activeProjects }}/{{ $totalProjects }}</p>
                <p class="text-xs text-text-muted">Proyectos activos</p>
            </div>
        </div>
    </div>

    {{-- Charts row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Tendencia mensual --}}
        <div class="lg:col-span-2 card">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-text-primary">Tendencia de Gastos</h2>
                    <p class="text-xs text-text-muted">Últimos 12 meses</p>
                </div>
            </div>
            <div class="h-64" x-data="trendChart()" x-init="init()">
                <canvas id="trend-chart"></canvas>
            </div>
        </div>

        {{-- Distribución por categoría (donut) --}}
        <div class="card">
            <h2 class="text-base font-semibold text-text-primary mb-4">Gastos por Categoría</h2>
            @if($expenseByCategory->isEmpty())
                <div class="flex items-center justify-center h-52 text-text-muted text-sm">
                    Sin datos para el período
                </div>
            @else
                <div class="h-52" x-data="categoryChart()" x-init="init()">
                    <canvas id="category-chart"></canvas>
                </div>
                <div class="mt-4 space-y-2">
                    @php
                        $catColors = ['#0230c8', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#6b7280'];
                    @endphp
                    @foreach($expenseByCategory->take(5) as $i => $cat)
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-2.5 h-2.5 rounded-full" style="background: {{ $catColors[$i] ?? '#9ca3af' }}"></div>
                                <span class="text-text-secondary">{{ $categoryLabels[$cat->category] ?? $cat->category }}</span>
                            </div>
                            <span class="font-medium text-text-primary">${{ number_format($cat->total, 0, '.', ',') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Budget comparison + Top projects --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Presupuesto vs Gasto --}}
        <div class="card">
            <h2 class="text-base font-semibold text-text-primary mb-4">Presupuesto vs Gasto Real</h2>
            @if($budgetComparison->isEmpty())
                <div class="flex items-center justify-center py-8 text-text-muted text-sm">Sin proyectos activos</div>
            @else
                <div class="space-y-4">
                    @foreach($budgetComparison as $comp)
                        @php
                            $p = min($comp['percent'], 100);
                            $barColor = $comp['percent'] >= 90 ? 'bg-danger' : ($comp['percent'] >= 70 ? 'bg-warning' : 'bg-primary-500');
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="font-medium text-text-primary truncate max-w-[60%]">{{ $comp['name'] }}</span>
                                <span class="text-text-muted text-xs">
                                    ${{ number_format($comp['spent'], 0, '.', ',') }} / ${{ number_format($comp['budget'], 0, '.', ',') }}
                                </span>
                            </div>
                            <div class="w-full h-2.5 bg-surface-main rounded-full overflow-hidden">
                                <div class="{{ $barColor }} h-full rounded-full transition-all duration-500" style="width: {{ $p }}%"></div>
                            </div>
                            <div class="flex justify-between mt-0.5">
                                <span class="text-[10px] text-text-muted">{{ $comp['percent'] }}% usado</span>
                                @if($comp['percent'] >= 90)
                                    <span class="text-[10px] font-semibold text-danger">⚠ Sobrepresupuesto</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Top 5 proyectos por gasto --}}
        <div class="table-container">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-text-primary">Top Proyectos por Gasto</h2>
                <p class="text-xs text-text-muted">Período seleccionado</p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Proyecto</th>
                        <th class="text-right">Presupuesto</th>
                        <th class="text-right">Gastado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProjects as $i => $proj)
                        <tr>
                            <td>
                                <span class="w-6 h-6 rounded-lg bg-primary-100 text-primary-700 text-xs font-bold flex items-center justify-center">
                                    {{ $i + 1 }}
                                </span>
                            </td>
                            <td>
                                <p class="font-medium">{{ $proj->name }}</p>
                                <p class="text-xs text-text-muted">{{ $proj->client ?? 'Sin cliente' }}</p>
                            </td>
                            <td class="text-right text-sm text-text-secondary">${{ number_format($proj->budget, 0, '.', ',') }}</td>
                            <td class="text-right text-sm font-semibold text-text-primary">${{ number_format($proj->total_spent, 0, '.', ',') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-8 text-text-muted">Sin datos</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Summary cards bottom --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card text-center">
            <p class="text-3xl font-bold text-primary-600">{{ $totalProjects }}</p>
            <p class="text-xs text-text-muted mt-1">Proyectos totales</p>
        </div>
        <div class="card text-center">
            <p class="text-3xl font-bold text-green-600">{{ $totalSuppliers }}</p>
            <p class="text-xs text-text-muted mt-1">Proveedores</p>
        </div>
        <div class="card text-center">
            <p class="text-3xl font-bold text-amber-600">{{ $requisitionsPending }}</p>
            <p class="text-xs text-text-muted mt-1">Requisiciones pendientes</p>
        </div>
        <div class="card text-center">
            <p class="text-3xl font-bold text-sky-600">${{ number_format($avgExpense, 0, '.', ',') }}</p>
            <p class="text-xs text-text-muted mt-1">Gasto promedio</p>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('trendChart', () => ({
        init() {
            const ctx = document.getElementById('trend-chart');
            if (!ctx) return;
            const data = @json($monthlyData);
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.short),
                    datasets: [{
                        label: 'Gastos',
                        data: data.map(d => d.total),
                        borderColor: '#0230c8',
                        backgroundColor: 'rgba(2, 48, 200, 0.08)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#0230c8',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1E1B2E',
                            padding: 12,
                            cornerRadius: 8,
                            titleFont: { family: 'Plus Jakarta Sans' },
                            bodyFont: { family: 'Plus Jakarta Sans' },
                            callbacks: { label: ctx => `$${ctx.parsed.y.toLocaleString()}` }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: '#9CA3AF' }
                        },
                        y: {
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: {
                                font: { family: 'Plus Jakarta Sans', size: 11 },
                                color: '#9CA3AF',
                                callback: v => `$${(v / 1000).toFixed(0)}k`
                            }
                        }
                    }
                }
            });
        }
    }));

    Alpine.data('categoryChart', () => ({
        init() {
            const ctx = document.getElementById('category-chart');
            if (!ctx) return;
            const data = @json($expenseByCategory);
            const labels = @json($categoryLabels);
            const colors = ['#0230c8', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#6b7280'];
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.map(d => labels[d.category] || d.category),
                    datasets: [{
                        data: data.map(d => d.total),
                        backgroundColor: colors.slice(0, data.length),
                        borderWidth: 0,
                        hoverOffset: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1E1B2E',
                            padding: 10,
                            cornerRadius: 8,
                            titleFont: { family: 'Plus Jakarta Sans' },
                            bodyFont: { family: 'Plus Jakarta Sans' },
                            callbacks: { label: ctx => `$${ctx.parsed.toLocaleString()}` }
                        }
                    }
                }
            });
        }
    }));
</script>
@endscript
