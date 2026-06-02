<div>

    {{-- ── Page Header ──────────────────────────────────── --}}
    <x-page-header subtitle="Panel de Control">
        <x-slot:heading>
            {{ explode(' ', auth()->user()->name ?? 'Usuario')[0] }},
            <span class="text-text-secondary font-normal">aquí está el resumen de hoy.</span>
        </x-slot:heading>
        <x-slot:actions>
            <a href="{{ url('/requisiciones') }}" class="btn-secondary">
                <i data-lucide="clipboard-list" class="w-3.5 h-3.5"></i>
                Requisiciones
            </a>
            <a href="{{ url('/gastos') }}" class="btn-primary">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                Nuevo gasto
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- ── KPI Strip ─────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">

        <div class="stat-card">
            <div class="stat-icon bg-primary-50">
                <i data-lucide="hard-hat" class="w-[18px] h-[18px] text-primary-600"></i>
            </div>
            <div class="min-w-0">
                <p class="text-h2 font-bold text-text-primary leading-none tabular-nums">{{ $activeProjects }}</p>
                <p class="text-xs-fluid text-text-muted mt-0.5">Proyectos activos</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-emerald-50">
                <i data-lucide="trending-up" class="w-[18px] h-[18px] text-emerald-600"></i>
            </div>
            <div class="min-w-0">
                <p class="text-h2 font-bold text-text-primary leading-none truncate tabular-nums">${{ number_format($monthExpenses, 0, '.', ',') }}</p>
                <p class="text-xs-fluid text-text-muted mt-0.5">Gasto del mes</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-amber-50">
                <i data-lucide="clock" class="w-[18px] h-[18px] text-amber-600"></i>
            </div>
            <div class="min-w-0">
                <p class="text-h2 font-bold text-text-primary leading-none tabular-nums">{{ $pendingRequisitions }}</p>
                <p class="text-xs-fluid text-text-muted mt-0.5">Req. pendientes</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-sky-50">
                <i data-lucide="truck" class="w-[18px] h-[18px] text-sky-600"></i>
            </div>
            <div class="min-w-0">
                <p class="text-h2 font-bold text-text-primary leading-none tabular-nums">{{ $totalSuppliers }}</p>
                <p class="text-xs-fluid text-text-muted mt-0.5">Proveedores</p>
            </div>
        </div>

    </div>

    {{-- ── Main grid: Chart + Metrics ───────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        {{-- Gráfica de gastos --}}
        <div class="lg:col-span-2 card">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-small font-semibold text-text-primary">Gastos Mensuales</h2>
                    <p class="text-xs-fluid text-text-muted mt-0.5">Últimos 6 meses</p>
                </div>
                <span class="inline-flex items-center gap-1 text-xs-fluid font-semibold text-text-secondary bg-surface-main border border-border px-2.5 py-1 rounded-md tabular-nums">
                    ${{ number_format($totalExpenses, 0, '.', ',') }}
                    <span class="font-normal text-text-muted">acumulado</span>
                </span>
            </div>
            <div class="h-44" x-data="chartComponent()" x-init="initChart()">
                <canvas id="monthly-expenses-chart"></canvas>
            </div>
        </div>

        {{-- Métricas operativas --}}
        <div class="card flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-small font-semibold text-text-primary">Métricas</h2>
                <a href="{{ url('/reportes') }}"
                    class="link-more">
                    Ver reportes
                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                </a>
            </div>
            <div class="flex-1 space-y-0">

                <div class="metric-row">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded bg-primary-50 flex items-center justify-center">
                            <i data-lucide="briefcase" class="w-3 h-3 text-primary-600"></i>
                        </div>
                        <span class="text-small text-text-secondary">Total proyectos</span>
                    </div>
                    <span class="text-small font-semibold text-text-primary tabular-nums">{{ $totalProjects }}</span>
                </div>

                <div class="metric-row">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded bg-emerald-50 flex items-center justify-center">
                            <i data-lucide="wallet" class="w-3 h-3 text-emerald-600"></i>
                        </div>
                        <span class="text-small text-text-secondary">Gasto total</span>
                    </div>
                    <span class="text-small font-semibold text-text-primary tabular-nums">${{ number_format($totalExpenses, 0, '.', ',') }}</span>
                </div>

                <div class="metric-row">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded bg-sky-50 flex items-center justify-center">
                            <i data-lucide="users" class="w-3 h-3 text-sky-600"></i>
                        </div>
                        <span class="text-small text-text-secondary">Proveedores</span>
                    </div>
                    <span class="text-small font-semibold text-text-primary tabular-nums">{{ $totalSuppliers }}</span>
                </div>

                <div class="metric-row">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded bg-emerald-50 flex items-center justify-center">
                            <i data-lucide="check-circle" class="w-3 h-3 text-emerald-600"></i>
                        </div>
                        <span class="text-small text-text-secondary">Req. aprobadas</span>
                    </div>
                    <span class="text-small font-semibold text-text-primary tabular-nums">
                        {{ \App\Models\Requisition::where('status', 'aprobada')->count() }}
                    </span>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Recent tables ─────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Proyectos recientes --}}
        <div class="table-container">
            <div class="px-4 py-3 flex items-center justify-between border-b border-border">
                <h2 class="text-small font-semibold text-text-primary">Proyectos Recientes</h2>
                <a href="{{ url('/proyectos') }}"
                    class="link-more">
                    Ver todos
                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                </a>
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
                                <p class="font-medium text-text-primary text-small">{{ $project->name }}</p>
                                <p class="text-xs-fluid text-text-muted">{{ $project->client ?? '—' }}</p>
                            </td>
                            <td>
                                <x-status-badge :status="$project->status" :map="['activo' => 'success', 'en_pausa' => 'warning', 'completado' => 'primary', 'cancelado' => 'danger']" />
                            </td>
                            <td class="text-right text-small font-semibold text-text-primary tabular-nums">
                                ${{ number_format($project->budget, 0, '.', ',') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <x-empty-state icon="folder-open" title="Sin proyectos registrados" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Requisiciones recientes --}}
        <div class="table-container">
            <div class="px-4 py-3 flex items-center justify-between border-b border-border">
                <h2 class="text-small font-semibold text-text-primary">Requisiciones Recientes</h2>
                <a href="{{ url('/requisiciones') }}"
                    class="link-more">
                    Ver todas
                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                </a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Estado</th>
                        <th>Proyecto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRequisitions as $req)
                        <tr>
                            <td>
                                <p class="font-medium text-text-primary text-small">
                                    {{ $req->number ?? 'REQ-' . $req->id }}
                                </p>
                                <p class="text-xs-fluid text-text-muted">{{ $req->creator->name ?? '' }}</p>
                            </td>
                            <td>
                                <x-status-badge :status="$req->status" :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
                            </td>
                            <td class="text-small text-text-secondary">{{ $req->project->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <x-empty-state icon="clipboard" title="Sin requisiciones" />
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
                        backgroundColor: (ctx) => {
                            const chart = ctx.chart;
                            const { ctx: c, chartArea } = chart;
                            if (!chartArea) return 'rgba(2,48,200,0.12)';
                            const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                            gradient.addColorStop(0, 'rgba(2,48,200,0.18)');
                            gradient.addColorStop(1, 'rgba(2,48,200,0.04)');
                            return gradient;
                        },
                        borderColor: 'rgba(2,48,200,0.7)',
                        borderWidth: 1.5,
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0F1117',
                            titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: '600' },
                            bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: (ctx) => ` $${ctx.parsed.y.toLocaleString()}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: '#9299A8' }
                        },
                        y: {
                            grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                            border: { display: false, dash: [4, 4] },
                            ticks: {
                                font: { family: 'Plus Jakarta Sans', size: 11 },
                                color: '#9299A8',
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
