<div>
    {{-- ── Page header ─────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-xl font-bold text-text-primary leading-tight">
                Bienvenido, {{ explode(' ', auth()->user()->name ?? 'Usuario')[0] }}
            </h1>
            <p class="text-sm text-text-muted mt-0.5">Aquí tienes un resumen de hoy</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ url('/requisiciones') }}" class="btn-secondary">
                <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                Requisiciones
            </a>
            <a href="{{ url('/gastos') }}" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nuevo gasto
            </a>
        </div>
    </div>

    {{-- ── Hero banner ──────────────────────────────────── --}}
    <div class="rounded-2xl bg-primary-600 p-7 text-white mb-6 relative overflow-hidden">
        {{-- subtle geometric decoration --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-white/5"></div>
            <div class="absolute -right-4 -bottom-20 w-48 h-48 rounded-full bg-white/5"></div>
            <div class="absolute right-40 top-4 w-24 h-24 rounded-full bg-white/5"></div>
        </div>
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-primary-200 text-xs font-semibold tracking-widest uppercase mb-1.5">Panel de Control</p>
                <h2 class="text-2xl font-bold mb-1 leading-tight">Muulsinik ERP</h2>
                <p class="text-primary-200 text-sm max-w-md leading-relaxed">
                    Gestiona proyectos de construcción, controla gastos y procesa requisiciones en un solo lugar.
                </p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <div class="bg-white/10 rounded-xl px-4 py-3 text-center min-w-[80px]">
                    <p class="text-2xl font-bold leading-none">{{ $activeProjects }}</p>
                    <p class="text-primary-200 text-xs mt-1">Activos</p>
                </div>
                <div class="bg-white/10 rounded-xl px-4 py-3 text-center min-w-[80px]">
                    <p class="text-2xl font-bold leading-none">{{ $pendingRequisitions }}</p>
                    <p class="text-primary-200 text-xs mt-1">Pendientes</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Stats cards ──────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-icon bg-primary-50">
                <i data-lucide="hard-hat" class="w-5 h-5 text-primary-600"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-bold text-text-primary leading-none">{{ $activeProjects }}</p>
                <p class="text-xs text-text-muted mt-1">Proyectos activos</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-emerald-50">
                <i data-lucide="wallet" class="w-5 h-5 text-emerald-600"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-bold text-text-primary leading-none truncate">${{ number_format($monthExpenses, 0, '.', ',') }}</p>
                <p class="text-xs text-text-muted mt-1">Gasto del mes</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-amber-50">
                <i data-lucide="clock" class="w-5 h-5 text-amber-500"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-bold text-text-primary leading-none">{{ $pendingRequisitions }}</p>
                <p class="text-xs text-text-muted mt-1">Req. pendientes</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-sky-50">
                <i data-lucide="truck" class="w-5 h-5 text-sky-600"></i>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-bold text-text-primary leading-none">{{ $totalSuppliers }}</p>
                <p class="text-xs text-text-muted mt-1">Proveedores</p>
            </div>
        </div>
    </div>

    {{-- ── Main grid: Chart + Summary ──────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

        {{-- Chart --}}
        <div class="lg:col-span-2 card">
            <div class="flex items-start justify-between mb-5">
                <div>
                    <h2 class="text-sm font-semibold text-text-primary">Gastos Mensuales</h2>
                    <p class="text-xs text-text-muted mt-0.5">Últimos 6 meses</p>
                </div>
                <span class="text-xs font-semibold text-text-muted bg-surface-main px-2.5 py-1 rounded-lg">
                    ${{ number_format($totalExpenses, 0, '.', ',') }} acumulado
                </span>
            </div>
            <div class="h-48" x-data="chartComponent()" x-init="initChart()">
                <canvas id="monthly-expenses-chart"></canvas>
            </div>
        </div>

        {{-- Quick stats --}}
        <div class="card flex flex-col">
            <h2 class="text-sm font-semibold text-text-primary mb-4">Resumen</h2>
            <div class="space-y-2.5 flex-1">
                <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-primary-50 flex items-center justify-center">
                            <i data-lucide="briefcase" class="w-3.5 h-3.5 text-primary-600"></i>
                        </div>
                        <span class="text-sm text-text-secondary">Total proyectos</span>
                    </div>
                    <span class="text-sm font-bold text-text-primary">{{ $totalProjects }}</span>
                </div>

                <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-3.5 h-3.5 text-emerald-600"></i>
                        </div>
                        <span class="text-sm text-text-secondary">Gasto total</span>
                    </div>
                    <span class="text-sm font-bold text-text-primary">${{ number_format($totalExpenses, 0, '.', ',') }}</span>
                </div>

                <div class="flex items-center justify-between py-2.5 border-b border-gray-50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-sky-50 flex items-center justify-center">
                            <i data-lucide="users" class="w-3.5 h-3.5 text-sky-600"></i>
                        </div>
                        <span class="text-sm text-text-secondary">Proveedores</span>
                    </div>
                    <span class="text-sm font-bold text-text-primary">{{ $totalSuppliers }}</span>
                </div>

                <div class="flex items-center justify-between py-2.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center">
                            <i data-lucide="clipboard-check" class="w-3.5 h-3.5 text-amber-600"></i>
                        </div>
                        <span class="text-sm text-text-secondary">Req. aprobadas</span>
                    </div>
                    <span class="text-sm font-bold text-text-primary">
                        {{ \App\Models\Requisition::where('status', 'aprobada')->count() }}
                    </span>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100">
                <a href="{{ url('/reportes') }}"
                    class="flex items-center justify-center gap-1.5 text-xs font-semibold text-primary-600 hover:text-primary-700 transition">
                    Ver reportes completos
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- ── Recent tables ────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Proyectos recientes --}}
        <div class="table-container">
            <div class="px-5 py-3.5 flex items-center justify-between border-b border-gray-100">
                <h2 class="text-sm font-semibold text-text-primary">Proyectos Recientes</h2>
                <a href="{{ url('/proyectos') }}"
                    class="text-xs font-semibold text-primary-600 hover:text-primary-700 transition">Ver todos →</a>
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
                        @php
                            $statusColors = [
                                'activo'     => 'badge-success',
                                'en_pausa'   => 'badge-warning',
                                'completado' => 'badge-primary',
                                'cancelado'  => 'badge-danger',
                            ];
                        @endphp
                        <tr>
                            <td>
                                <p class="font-medium text-text-primary text-[13px]">{{ $project->name }}</p>
                                <p class="text-[11px] text-text-muted">{{ $project->client ?? 'Sin cliente' }}</p>
                            </td>
                            <td>
                                <span class="badge {{ $statusColors[$project->status] ?? 'badge-secondary' }}">
                                    {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                </span>
                            </td>
                            <td class="text-right text-[13px] font-semibold text-text-primary">
                                ${{ number_format($project->budget, 0, '.', ',') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-10">
                                <i data-lucide="folder-open" class="w-8 h-8 mx-auto mb-2 text-text-muted opacity-30"></i>
                                <p class="text-sm text-text-muted">Sin proyectos registrados</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Requisiciones recientes --}}
        <div class="table-container">
            <div class="px-5 py-3.5 flex items-center justify-between border-b border-gray-100">
                <h2 class="text-sm font-semibold text-text-primary">Requisiciones Recientes</h2>
                <a href="{{ url('/requisiciones') }}"
                    class="text-xs font-semibold text-primary-600 hover:text-primary-700 transition">Ver todas →</a>
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
                        @php
                            $reqStatusColors = [
                                'borrador'  => 'badge-secondary',
                                'pendiente' => 'badge-warning',
                                'aprobada'  => 'badge-success',
                                'rechazada' => 'badge-danger',
                            ];
                        @endphp
                        <tr>
                            <td>
                                <p class="font-medium text-text-primary text-[13px]">
                                    {{ $req->number ?? 'REQ-' . $req->id }}
                                </p>
                                <p class="text-[11px] text-text-muted">{{ $req->creator->name ?? '' }}</p>
                            </td>
                            <td>
                                <span class="badge {{ $reqStatusColors[$req->status] ?? 'badge-secondary' }}">
                                    {{ ucfirst($req->status) }}
                                </span>
                            </td>
                            <td class="text-[13px] text-text-secondary">{{ $req->project->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-10">
                                <i data-lucide="clipboard" class="w-8 h-8 mx-auto mb-2 text-text-muted opacity-30"></i>
                                <p class="text-sm text-text-muted">Sin requisiciones</p>
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
                            backgroundColor: '#111827',
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
                            ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: '#9CA3AF' }
                        },
                        y: {
                            grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                            border: { display: false, dash: [4, 4] },
                            ticks: {
                                font: { family: 'Plus Jakarta Sans', size: 11 },
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
