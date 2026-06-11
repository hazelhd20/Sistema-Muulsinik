<div>
    @assets
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endassets

    {{-- ── Page Header ──────────────────────────────────── --}}
    <x-page-header subtitle="Panel de Control">
        <x-slot:heading>
            {{ explode(' ', auth()->user()->name ?? 'Usuario')[0] }},
            <span class="text-text-secondary font-normal">aquí está el resumen de hoy.</span>
        </x-slot:heading>
        <x-slot:actions>
            <x-button href="{{ url('/requisiciones') }}" variant="secondary" icon="clipboard-list">
                Requisiciones
            </x-button>
            <x-button href="{{ url('/gastos') }}" variant="primary" icon="plus">
                Nuevo gasto
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- ── KPI Strip ─────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">

        <div class="stat-card">
            <div class="stat-icon bg-primary-50">
                <x-lucide-hard-hat class="w-[18px] h-[18px] text-primary-600" />
            </div>
            <div class="min-w-0">
                <p class="text-h2 font-bold text-text-primary leading-none tabular-nums">{{ $activeProjects }}</p>
                <p class="text-xs-fluid text-text-muted mt-0.5">Proyectos activos</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-success-light">
                <x-lucide-trending-up class="w-[18px] h-[18px] text-success" />
            </div>
            <div class="min-w-0">
                <p class="text-h2 font-bold text-text-primary leading-none truncate tabular-nums">${{ number_format($monthExpenses, 0, '.', ',') }}</p>
                <p class="text-xs-fluid text-text-muted mt-0.5">Gasto del mes</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-warning-light">
                <x-lucide-clock class="w-[18px] h-[18px] text-warning" />
            </div>
            <div class="min-w-0">
                <p class="text-h2 font-bold text-text-primary leading-none tabular-nums">{{ $pendingRequisitions }}</p>
                <p class="text-xs-fluid text-text-muted mt-0.5">Requisiciones pendientes</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-danger-light">
                <x-lucide-truck class="w-[18px] h-[18px] text-danger" />
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
        <div class="lg:col-span-2 card p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="card-title">Gastos Mensuales</h2>
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
        <div class="card p-6 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h2 class="card-title">Métricas</h2>
                <a href="{{ url('/reportes') }}"
                    class="link-more">
                    Ver reportes
                    <x-lucide-arrow-right class="w-3 h-3" />
                </a>
            </div>
            <div class="flex-1 space-y-0">

                <div class="metric-row">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded bg-primary-50 flex items-center justify-center">
                            <x-lucide-briefcase class="w-3 h-3 text-primary-600" />
                        </div>
                        <span class="text-small text-text-secondary">Total proyectos</span>
                    </div>
                    <span class="text-small font-semibold text-text-primary tabular-nums">{{ $totalProjects }}</span>
                </div>

                <div class="metric-row">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded bg-warning-light flex items-center justify-center">
                            <x-lucide-wallet class="w-3 h-3 text-warning" />
                        </div>
                        <span class="text-small text-text-secondary">Gasto total</span>
                    </div>
                    <span class="text-small font-semibold text-text-primary tabular-nums">${{ number_format($totalExpenses, 0, '.', ',') }}</span>
                </div>

                <div class="metric-row">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded bg-danger-light flex items-center justify-center">
                            <x-lucide-users class="w-3 h-3 text-danger" />
                        </div>
                        <span class="text-small text-text-secondary">Proveedores</span>
                    </div>
                    <span class="text-small font-semibold text-text-primary tabular-nums">{{ $totalSuppliers }}</span>
                </div>

                <div class="metric-row">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded bg-success-light flex items-center justify-center">
                            <x-lucide-check-circle class="w-3 h-3 text-success" />
                        </div>
                        <span class="text-small text-text-secondary">Requisiciones aprobadas</span>
                    </div>
                    <span class="text-small font-semibold text-text-primary tabular-nums">
                        {{ $approvedRequisitions }}
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
                <h2 class="card-title">Proyectos Recientes</h2>
                <a href="{{ url('/proyectos') }}"
                    class="link-more">
                    Ver todos
                    <x-lucide-arrow-right class="w-3 h-3" />
                </a>
            </div>
            <table wire:loading.class="hidden" wire:target="previousPage, nextPage, gotoPage">
                <thead>
                    <tr>
                        <th>Proyecto</th>
                        <th>Estado</th>
                        <th class="text-right">Presupuesto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentProjects as $project)
                        <tr @click="Livewire.navigate('{{ url('/proyectos') }}')" class="cursor-pointer hover:bg-surface-hover">
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
            
            <table wire:loading.class.remove="hidden" class="hidden w-full">
                <thead>
                    <tr>
                        <th>Proyecto</th>
                        <th>Estado</th>
                        <th class="text-right">Presupuesto</th>
                    </tr>
                </thead>
                <tbody>
                    @for($i=0; $i<3; $i++)
                    <tr>
                        <td>
                            <x-skeleton class="h-4  rounded w-3/4 mb-1" />
                            <x-skeleton class="h-3  rounded w-1/2" />
                        </td>
                        <td><x-skeleton class="h-6  rounded-full w-20" /></td>
                        <td class="text-right"><x-skeleton class="h-4  rounded w-16 ml-auto" /></td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        {{-- Requisiciones recientes --}}
        <div class="table-container">
            <div class="px-4 py-3 flex items-center justify-between border-b border-border">
                <h2 class="card-title">Requisiciones Recientes</h2>
                <a href="{{ url('/requisiciones') }}"
                    class="link-more">
                    Ver todas
                    <x-lucide-arrow-right class="w-3 h-3" />
                </a>
            </div>
            <table wire:loading.class="hidden" wire:target="previousPage, nextPage, gotoPage">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Estado</th>
                        <th>Proyecto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRequisitions as $req)
                        <tr @click="Livewire.navigate('{{ route('cotizador.wizard', ['id' => $req->id]) }}')" class="cursor-pointer hover:bg-surface-hover">
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
            
            <table wire:loading.class.remove="hidden" class="hidden w-full">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Estado</th>
                        <th>Proyecto</th>
                    </tr>
                </thead>
                <tbody>
                    @for($i=0; $i<3; $i++)
                    <tr>
                        <td>
                            <x-skeleton class="h-4  rounded w-1/2 mb-1" />
                            <x-skeleton class="h-3  rounded w-3/4" />
                        </td>
                        <td><x-skeleton class="h-6  rounded-full w-20" /></td>
                        <td><x-skeleton class="h-4  rounded w-2/3" /></td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
    
    @script
    <script>
        Alpine.data('chartComponent', () => ({
            initChart() {
                if (typeof Chart === 'undefined') {
                    console.warn('Chart.js no está cargado. Usa la directiva @@assets de Livewire.');
                    return;
                }
                this.renderChart();
            },
            renderChart() {
                const ctx = document.getElementById('monthly-expenses-chart');
                if (!ctx) return;
    
                const data = @json($monthlyExpenses);
    
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(d => d.month),
                        datasets: [{
                            // Paleta mapeada a design tokens:
                            // rgba(2,48,200,X) = var(--color-primary-600) con opacidad X
                            // '#0F1117'        = var(--color-text-primary)
                            // '#9299A8'        = var(--color-text-muted)
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
</div>
