<div>
    {{-- Hero banner (inspirado en el diseño de referencia) --}}
    <div class="rounded-2xl bg-gradient-to-br from-primary-600 via-primary-500 to-primary-400 p-8 text-white mb-6 relative overflow-hidden">
        {{-- Decoración de fondo --}}
        <div class="absolute top-0 right-0 w-64 h-64 opacity-10">
            <svg viewBox="0 0 200 200" fill="none">
                <path d="M100 0L130 60L200 70L150 120L160 190L100 160L40 190L50 120L0 70L70 60Z" fill="currentColor"/>
            </svg>
        </div>
        <div class="relative z-10">
            <p class="text-primary-200 text-sm font-semibold tracking-wider uppercase mb-2">Panel de Control</p>
            <h1 class="text-2xl md:text-3xl font-bold mb-2">
                Bienvenido a Muulsinik ERP
            </h1>
            <p class="text-primary-100 max-w-xl">
                Gestiona tus proyectos de construcción, controla gastos y procesa requisiciones desde un solo lugar.
            </p>
        </div>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Proyectos activos --}}
        <div class="stat-card">
            <div class="stat-icon bg-primary-100">
                <i data-lucide="hard-hat" class="w-5 h-5 text-primary-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-text-primary">{{ $activeProjects }}</p>
                <p class="text-xs text-text-muted">Proyectos activos</p>
            </div>
        </div>

        {{-- Gasto del mes --}}
        <div class="stat-card">
            <div class="stat-icon bg-green-100">
                <i data-lucide="wallet" class="w-5 h-5 text-green-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-text-primary">${{ number_format($monthExpenses, 0, '.', ',') }}</p>
                <p class="text-xs text-text-muted">Gasto del mes</p>
            </div>
        </div>

        {{-- Requisiciones pendientes --}}
        <div class="stat-card">
            <div class="stat-icon bg-amber-100">
                <i data-lucide="clipboard-list" class="w-5 h-5 text-amber-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-text-primary">{{ $pendingRequisitions }}</p>
                <p class="text-xs text-text-muted">Requisiciones pendientes</p>
            </div>
        </div>

        {{-- Proveedores --}}
        <div class="stat-card">
            <div class="stat-icon bg-sky-100">
                <i data-lucide="truck" class="w-5 h-5 text-sky-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-text-primary">{{ $totalSuppliers }}</p>
                <p class="text-xs text-text-muted">Proveedores registrados</p>
            </div>
        </div>
    </div>

    {{-- Main grid: Chart + Recent Projects --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Chart de gastos mensuales --}}
        <div class="lg:col-span-2 card">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-base font-semibold text-text-primary">Gastos Mensuales</h2>
                    <p class="text-xs text-text-muted">Últimos 6 meses</p>
                </div>
                <span class="badge badge-primary">
                    ${{ number_format($totalExpenses, 0, '.', ',') }} total
                </span>
            </div>
            <div class="relative h-52" x-data="chartComponent()" x-init="initChart()">
                <canvas id="monthly-expenses-chart"></canvas>
            </div>
        </div>

        {{-- Right column: Quick stats --}}
        <div class="card">
            <h2 class="text-base font-semibold text-text-primary mb-4">Resumen Global</h2>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl bg-surface-main">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center">
                            <i data-lucide="briefcase" class="w-4 h-4 text-primary-600"></i>
                        </div>
                        <span class="text-sm font-medium text-text-primary">Total Proyectos</span>
                    </div>
                    <span class="text-sm font-bold text-text-primary">{{ $totalProjects }}</span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-surface-main">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-4 h-4 text-green-600"></i>
                        </div>
                        <span class="text-sm font-medium text-text-primary">Gasto Total</span>
                    </div>
                    <span class="text-sm font-bold text-text-primary">${{ number_format($totalExpenses, 0, '.', ',') }}</span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-surface-main">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4 text-amber-600"></i>
                        </div>
                        <span class="text-sm font-medium text-text-primary">Proveedores</span>
                    </div>
                    <span class="text-sm font-bold text-text-primary">{{ $totalSuppliers }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent tables --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Proyectos recientes --}}
        <div class="table-container">
            <div class="px-5 py-4 flex items-center justify-between border-b border-gray-100">
                <h2 class="text-base font-semibold text-text-primary">Proyectos Recientes</h2>
                <a href="{{ url('/proyectos') }}" class="text-xs font-medium text-primary-600 hover:text-primary-700">Ver todos</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Proyecto</th>
                        <th>Estado</th>
                        <th class="text-right">Presupuesto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentProjects as $project)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $project->name }}</p>
                                <p class="text-xs text-text-muted">{{ $project->client ?? 'Sin cliente' }}</p>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'activo' => 'badge-success',
                                        'en_pausa' => 'badge-warning',
                                        'completado' => 'badge-primary',
                                        'cancelado' => 'badge-danger',
                                    ];
                                @endphp
                                <span class="badge {{ $statusColors[$project->status] ?? 'badge-primary' }}">
                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                </span>
                            </td>
                            <td class="text-right font-medium">${{ number_format($project->budget, 0, '.', ',') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-text-muted py-8">
                                <i data-lucide="folder-open" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                <p>No hay proyectos registrados</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Requisiciones recientes --}}
        <div class="table-container">
            <div class="px-5 py-4 flex items-center justify-between border-b border-gray-100">
                <h2 class="text-base font-semibold text-text-primary">Requisiciones Recientes</h2>
                <a href="{{ url('/requisiciones') }}" class="text-xs font-medium text-primary-600 hover:text-primary-700">Ver todas</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Proyecto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRequisitions as $req)
                        <tr>
                            <td>
                                <p class="font-medium">{{ Str::limit($req->description, 30) }}</p>
                                <p class="text-xs text-text-muted">{{ $req->creator->name ?? '' }}</p>
                            </td>
                            <td>
                                @php
                                    $reqStatusColors = [
                                        'pendiente' => 'badge-warning',
                                        'aprobada' => 'badge-success',
                                        'rechazada' => 'badge-danger',
                                    ];
                                @endphp
                                <span class="badge {{ $reqStatusColors[$req->status] ?? 'badge-primary' }}">
                                    {{ ucfirst($req->status) }}
                                </span>
                            </td>
                            <td class="text-sm text-text-secondary">{{ $req->project->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-text-muted py-8">
                                <i data-lucide="clipboard" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                <p>No hay requisiciones</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('chartComponent', () => ({
        initChart() {
            const ctx = document.getElementById('monthly-expenses-chart');
            if (!ctx) return;

            const data = @json($monthlyExpenses);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.month),
                    datasets: [{
                        label: 'Gastos',
                        data: data.map(d => d.total),
                        backgroundColor: 'rgba(139, 92, 246, 0.15)',
                        borderColor: 'rgba(139, 92, 246, 0.8)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1E1B2E',
                            titleFont: { family: 'Plus Jakarta Sans' },
                            bodyFont: { family: 'Plus Jakarta Sans' },
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: (ctx) => `$${ctx.parsed.y.toLocaleString()}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'Plus Jakarta Sans', size: 12 }, color: '#9CA3AF' }
                        },
                        y: {
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: {
                                font: { family: 'Plus Jakarta Sans', size: 12 },
                                color: '#9CA3AF',
                                callback: (v) => `$${(v / 1000).toFixed(0)}k`
                            }
                        }
                    }
                }
            });
        }
    }));
</script>
@endscript
