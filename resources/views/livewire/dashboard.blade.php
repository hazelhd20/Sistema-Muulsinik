<div>
    @assets
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endassets

    {{-- ── Page Header ──────────────────────────────────── --}}
    <x-page-header subtitle="Panel de Control" icon="layout-dashboard">
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
        <x-stat-card 
            title="Proyectos activos" 
            value="{{ $activeProjects }}" 
            icon="hard-hat" 
            color="primary" 
        />

        <x-stat-card 
            title="Gasto del mes" 
            value="${{ number_format($monthExpenses, 0, '.', ',') }}" 
            icon="trending-up" 
            color="success" 
        />

        <x-stat-card 
            title="Requisiciones pendientes" 
            value="{{ $pendingRequisitions }}" 
            icon="clock" 
            color="warning" 
        />

        <x-stat-card 
            title="Proveedores" 
            value="{{ $totalSuppliers }}" 
            icon="truck" 
            color="danger" 
        />

    </div>

    {{-- ── Main grid: Chart + Metrics ───────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        {{-- Gráfica de gastos --}}
        <x-card class="lg:col-span-2">
            <x-card.header title="Gastos Mensuales" subtitle="Últimos 6 meses">
                <x-slot:action>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-text-secondary bg-surface-main border border-border px-2.5 py-1 rounded-md tabular-nums">
                        ${{ number_format($totalExpenses, 0, '.', ',') }}
                        <span class="font-normal text-text-muted">acumulado</span>
                    </span>
                </x-slot:action>
            </x-card.header>
            <x-card.body class="pt-0">
                @if($totalExpenses == 0)
                    <div class="py-6">
                        <x-empty-state icon="trending-up" title="Sin gastos registrados" message="Los datos de tus gastos se graficarán aquí." />
                    </div>
                @else
                    <div class="h-44" x-data="chartComponent()" x-init="initChart()">
                        <canvas id="monthly-expenses-chart"></canvas>
                    </div>
                @endif
            </x-card.body>
        </x-card>

        {{-- Métricas operativas --}}
        <x-card>
            <x-card.header title="Métricas">
                <x-slot:action>
                    <a href="{{ url('/reportes') }}" class="link-more">
                        Ver reportes
                        <x-lucide-arrow-right class="w-3 h-3" />
                    </a>
                </x-slot:action>
            </x-card.header>
            <x-card.body class="pt-0 flex flex-col justify-between">
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
            </x-card.body>
        </x-card>
    </div>

    {{-- ── Recent tables ─────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Proyectos recientes --}}
        <x-card>
            <x-card.header title="Proyectos Recientes">
                <x-slot:action>
                    <a href="{{ url('/proyectos') }}" class="link-more">
                        Ver todos
                        <x-lucide-arrow-right class="w-3 h-3" />
                    </a>
                </x-slot:action>
            </x-card.header>
            @if($recentProjects->isEmpty())
                <x-card.body class="pt-2">
                    <x-empty-state icon="folder-open" title="Sin proyectos registrados" />
                </x-card.body>
            @else
            <x-card.table>
                <table>
                <thead class="bg-surface-th border-b border-border">
                    <tr>
                        <th>Proyecto</th>
                        <th>Estado</th>
                        <th class="text-right">Presupuesto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentProjects as $project)
                        <tr @click="Livewire.navigate('{{ route('proyectos.show', $project->id) }}')" class="cursor-pointer group hover:bg-surface-hover/80 transition-colors duration-150">
                            <td>
                                <p class="font-medium text-text-primary text-small">{{ $project->name }}</p>
                                <p class="text-xs text-text-muted">{{ $project->client?->name ?? '—' }}</p>
                            </td>
                            <td>
                                <x-status-badge :status="$project->status" :map="['activo' => 'success', 'en_pausa' => 'warning', 'completado' => 'primary', 'cancelado' => 'danger']" />
                            </td>
                            <td class="text-right text-small font-semibold text-text-primary tabular-nums">
                                ${{ number_format($project->budget, 0, '.', ',') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </x-card.table>
            @endif
        </x-card>

        {{-- Requisiciones recientes --}}
        <x-card>
            <x-card.header title="Requisiciones Recientes">
                <x-slot:action>
                    <a href="{{ url('/requisiciones') }}" class="link-more">
                        Ver todas
                        <x-lucide-arrow-right class="w-3 h-3" />
                    </a>
                </x-slot:action>
            </x-card.header>
            @if($recentRequisitions->isEmpty())
                <x-card.body class="pt-2">
                    <x-empty-state icon="clipboard" title="Sin requisiciones" />
                </x-card.body>
            @else
            <x-card.table>
                <table>
                <thead class="bg-surface-th border-b border-border">
                    <tr>
                        <th>Número</th>
                        <th>Estado</th>
                        <th>Proyecto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentRequisitions as $req)
                        <tr @click="Livewire.navigate('{{ route('requisiciones.show', $req->id) }}')" class="cursor-pointer group hover:bg-surface-hover/80 transition-colors duration-150">
                            <td>
                                <p class="font-medium text-text-primary text-small">
                                    {{ $req->number ?? 'REQ-' . $req->id }}
                                </p>
                                <p class="text-xs text-text-muted">{{ $req->creator->name ?? '' }}</p>
                            </td>
                            <td>
                                <x-status-badge :status="$req->status" :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
                            </td>
                            <td class="text-small text-text-secondary">{{ $req->project->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </x-card.table>
            @endif
        </x-card>
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

                // Helper para convertir hex a rgba compatible con Canvas
                const hexToRgba = (hex, opacity) => {
                    hex = hex.trim();
                    if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
                        let c = hex.substring(1).split('');
                        if(c.length === 3){
                            c = [c[0], c[0], c[1], c[1], c[2], c[2]];
                        }
                        c = '0x' + c.join('');
                        return `rgba(${(c>>16)&255}, ${(c>>8)&255}, ${c&255}, ${opacity})`;
                    }
                    return hex;
                };

                // Obtener tokens de diseño dinámicamente del DOM
                const style = getComputedStyle(document.documentElement);
                const primaryHex = style.getPropertyValue('--color-primary-600').trim() || '#2563eb';
                const textPrimary = style.getPropertyValue('--color-text-primary').trim() || '#0f1117';
                const textMuted = style.getPropertyValue('--color-text-muted').trim() || '#475569';
                const borderLight = style.getPropertyValue('--color-border-light').trim() || 'rgba(0,0,0,0.04)';
    
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
                                if (!chartArea) return hexToRgba(primaryHex, 0.12);
                                const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                                gradient.addColorStop(0, hexToRgba(primaryHex, 0.18));
                                gradient.addColorStop(1, hexToRgba(primaryHex, 0.04));
                                return gradient;
                            },
                            borderColor: hexToRgba(primaryHex, 0.7),
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
                                backgroundColor: textPrimary,
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
                                ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: textMuted }
                            },
                            y: {
                                grid: { color: borderLight, drawBorder: false },
                                border: { display: false, dash: [4, 4] },
                                ticks: {
                                    font: { family: 'Plus Jakarta Sans', size: 11 },
                                    color: textMuted,
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
