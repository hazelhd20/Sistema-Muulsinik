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
            @if(auth()->user()?->hasPermission('requisiciones.ver') || auth()->user()?->hasPermission('*'))
                <x-button href="{{ url('/requisiciones') }}" variant="secondary" icon="clipboard-list" class="flex-1 sm:flex-initial justify-center">
                    Requisiciones
                </x-button>
            @endif
            @if(auth()->user()?->hasPermission('gastos.crear') || auth()->user()?->hasPermission('*'))
                <x-button href="{{ url('/gastos') }}" variant="primary" icon="plus" class="flex-1 sm:flex-initial justify-center">
                    Nuevo gasto
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- ── KPI Strip ─────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <x-stat-card 
            title="Proyectos activos" 
            value="{{ $activeProjects }}" 
            icon="hard-hat" 
            color="primary" 
            href="{{ url('/proyectos') }}"
        />

        <x-stat-card 
            title="Gasto del mes" 
            value="${{ number_format($monthExpenses, 0, '.', ',') }}" 
            icon="trending-up" 
            color="success" 
            href="{{ url('/gastos') }}"
        />

        <x-stat-card 
            title="Requisiciones pendientes" 
            value="{{ $pendingRequisitions }}" 
            icon="clock" 
            color="warning" 
            href="{{ url('/requisiciones?statusFilter=pendiente') }}"
        />

        <x-stat-card 
            title="Borradores y procesos" 
            value="{{ $pendingQuotations }}" 
            icon="file-edit" 
            color="info" 
            href="{{ url('/requisiciones?tab=borradores') }}"
        />

    </div>

    {{-- ── Main grid: Chart + Metrics ───────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        {{-- Gráfica de gastos --}}
        <x-card class="lg:col-span-2">
            <x-card.header title="Gastos Mensuales" subtitle="Últimos 6 meses">
                <x-slot:action>
                    <x-chip size="sm" class="tabular-nums font-semibold">
                        ${{ number_format($totalExpenses, 0, '.', ',') }}
                        <span class="font-normal text-text-muted">acumulado</span>
                    </x-chip>
                </x-slot:action>
            </x-card.header>
            <x-card.body class="pt-0">
                @if($totalExpenses == 0)
                    <div class="py-6">
                        <x-empty-state icon="trending-up" title="Sin gastos registrados" message="Los datos de tus gastos se graficarán aquí." />
                    </div>
                @else
                    <div class="h-64" x-data="chartComponent" x-init="initChart()" @window:theme-changed="updateChartTheme()">
                        <canvas id="monthly-expenses-chart"></canvas>
                    </div>
                @endif
            </x-card.body>
        </x-card>

        {{-- Métricas operativas --}}
        <x-card>
            <x-card.header title="Métricas">
                <x-slot:action>
                    @if(auth()->user()?->hasPermission('reportes.ver') || auth()->user()?->hasPermission('*'))
                        <a href="{{ url('/reportes') }}" class="link-more">
                            Ver reportes
                            <x-lucide-arrow-right class="w-3 h-3" />
                        </a>
                    @endif
                </x-slot:action>
            </x-card.header>
            <x-card.body class="pt-0 flex flex-col justify-between">
                <div class="flex-1 space-y-0">

                    <div class="metric-row">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 rounded bg-primary-50 flex items-center justify-center">
                                <x-lucide-building-2 class="w-3 h-3 text-primary-600" />
                            </div>
                            <span class="text-small text-text-secondary">Cartera de clientes</span>
                        </div>
                        <span class="text-small font-semibold text-text-primary tabular-nums">{{ $totalClients }}</span>
                    </div>

                    <div class="metric-row">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 rounded bg-purple-50 dark:bg-purple-500/15 flex items-center justify-center">
                                <x-lucide-briefcase class="w-3 h-3 text-purple-600" />
                            </div>
                            <span class="text-small text-text-secondary">Total proyectos</span>
                        </div>
                        <span class="text-small font-semibold text-text-primary tabular-nums">{{ $totalProjects }}</span>
                    </div>

                    <div class="metric-row">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 rounded bg-warning-light flex items-center justify-center">
                                <x-lucide-package class="w-3 h-3 text-warning" />
                            </div>
                            <span class="text-small text-text-secondary">Catálogo de productos</span>
                        </div>
                        <span class="text-small font-semibold text-text-primary tabular-nums">{{ $totalProducts }}</span>
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
                            <div class="w-5 h-5 rounded bg-cyan-50 dark:bg-cyan-500/15 flex items-center justify-center">
                                <x-lucide-file-text class="w-3 h-3 text-cyan-600" />
                            </div>
                            <span class="text-small text-text-secondary">Presupuestos rápidos</span>
                        </div>
                        <span class="text-small font-semibold text-text-primary tabular-nums">{{ $totalBudgets }}</span>
                    </div>

                    <div class="metric-row">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 rounded bg-success-light flex items-center justify-center">
                                <x-lucide-check-circle class="w-3 h-3 text-success" />
                            </div>
                            <span class="text-small text-text-secondary">Requisiciones aprobadas</span>
                        </div>
                        <span class="text-small font-semibold text-text-primary tabular-nums">{{ $approvedRequisitions }}</span>
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
                    @if(auth()->user()?->hasPermission('proyectos.ver') || auth()->user()?->hasPermission('*'))
                        <a href="{{ url('/proyectos') }}" class="link-more">
                            Ver todos
                            <x-lucide-arrow-right class="w-3 h-3" />
                        </a>
                    @endif
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
                        <tr @click="Livewire.navigate('{{ route('proyectos.show', $project->id) }}')" class="cursor-pointer group hover:bg-surface-hover transition-colors duration-150">
                            <td>
                                <p class="font-medium text-text-primary text-small">{{ $project->name }}</p>
                                <p class="text-xs-fluid text-text-muted">{{ $project->client?->name ?? '—' }}</p>
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
                    @if(auth()->user()?->hasPermission('requisiciones.ver') || auth()->user()?->hasPermission('*'))
                        <a href="{{ url('/requisiciones') }}" class="link-more">
                            Ver todas
                            <x-lucide-arrow-right class="w-3 h-3" />
                        </a>
                    @endif
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
                        <tr @click="Livewire.navigate('{{ route('requisiciones.show', $req->id) }}')" class="cursor-pointer group hover:bg-surface-hover transition-colors duration-150">
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
                    @endforeach
                </tbody>
            </table>
            </x-card.table>
            @endif
        </x-card>
    </div>
    
    @script
    <script>
        Alpine.data('chartComponent', () => {
            let chartInstance = null;
            let themeObserver = null;

            return {
                initChart() {
                    if (typeof Chart === 'undefined') {
                        console.warn('Chart.js no está cargado. Usa la directiva @@assets de Livewire.');
                        return;
                    }
                    this.waitForCanvas();

                    themeObserver = new MutationObserver(() => {
                        this.renderChart();
                    });
                    themeObserver.observe(document.documentElement, {
                        attributes: true,
                        attributeFilter: ['class']
                    });
                },
                waitForCanvas(attempts = 0) {
                    const ctx = document.getElementById('monthly-expenses-chart');
                    if (ctx && (ctx.clientWidth > 0 || attempts >= 15)) {
                        this.renderChart();
                    } else if (attempts < 20) {
                        setTimeout(() => this.waitForCanvas(attempts + 1), 25);
                    }
                },
                destroy() {
                    if (themeObserver) {
                        themeObserver.disconnect();
                        themeObserver = null;
                    }
                    if (chartInstance) {
                        chartInstance.destroy();
                        chartInstance = null;
                    }
                    const ctx = document.getElementById('monthly-expenses-chart');
                    if (ctx) {
                        const existing = Chart?.getChart(ctx);
                        if (existing) existing.destroy();
                    }
                },
                updateChartTheme() {
                    this.renderChart();
                },
                renderChart() {
                    const ctx = document.getElementById('monthly-expenses-chart');
                    if (!ctx) return;

                    // Destruir instancia previa si existe en este canvas o memoria (vital para SPA Livewire)
                    const existingChart = Chart.getChart(ctx);
                    if (existingChart) {
                        existingChart.destroy();
                    }
                    if (chartInstance) {
                        chartInstance.destroy();
                        chartInstance = null;
                    }
        
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

                // Mismo patrón que chartCanvas en app.js:
                // Leer variables base del :root/.dark (sin aliases @theme de Tailwind que no se resuelven)
                const style       = getComputedStyle(document.documentElement);
                const textPrimary = style.getPropertyValue('--text-primary').trim()   || '#0f1117';
                const textMuted   = style.getPropertyValue('--text-secondary').trim() || '#64748b';
                const borderLine  = style.getPropertyValue('--border-light').trim()   || '#f1f5f9';
                const borderColor = style.getPropertyValue('--border').trim()         || '#e2e8f0';
                const tooltipBg   = style.getPropertyValue('--surface-card').trim()   || '#ffffff';

                // Aplicar defaults globales de Chart.js
                Chart.defaults.color       = textMuted;
                Chart.defaults.borderColor = borderLine;

                if (Chart.defaults.plugins?.tooltip) {
                    Chart.defaults.plugins.tooltip.backgroundColor = tooltipBg;
                    Chart.defaults.plugins.tooltip.titleColor      = textPrimary;
                    Chart.defaults.plugins.tooltip.bodyColor       = textPrimary;
                    Chart.defaults.plugins.tooltip.footerColor     = textMuted;
                    Chart.defaults.plugins.tooltip.borderColor     = borderColor;
                    Chart.defaults.plugins.tooltip.borderWidth     = 1;
                }

                const primaryHex  = '#2563eb';
                const successHex  = '#0d9e6e';
                const fontToken   = style.getPropertyValue('--font-sans').trim();
                const fontFamily  = fontToken ? fontToken.split(',')[0].replace(/['"]/g, '') : 'Plus Jakarta Sans';
    
                chartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(d => d.month),
                        datasets: [
                            {
                                label: 'Requisiciones Aprobadas',
                                data: data.map(d => d.requisitions),
                                borderColor: successHex,
                                borderWidth: 2,
                                tension: 0.35,
                                pointRadius: 3,
                                pointHoverRadius: 6,
                                pointBackgroundColor: successHex,
                                fill: true,
                                backgroundColor: (ctx) => {
                                    const chart = ctx.chart;
                                    const { ctx: c, chartArea } = chart;
                                    if (!chartArea) return hexToRgba(successHex, 0.1);
                                    const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                                    gradient.addColorStop(0, hexToRgba(successHex, 0.22));
                                    gradient.addColorStop(1, hexToRgba(successHex, 0.0));
                                    return gradient;
                                }
                            },
                            {
                                label: 'Gastos Directos',
                                data: data.map(d => d.direct),
                                borderColor: primaryHex,
                                borderWidth: 2,
                                tension: 0.35,
                                pointRadius: 3,
                                pointHoverRadius: 6,
                                pointBackgroundColor: primaryHex,
                                fill: true,
                                backgroundColor: (ctx) => {
                                    const chart = ctx.chart;
                                    const { ctx: c, chartArea } = chart;
                                    if (!chartArea) return hexToRgba(primaryHex, 0.1);
                                    const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                                    gradient.addColorStop(0, hexToRgba(primaryHex, 0.25));
                                    gradient.addColorStop(1, hexToRgba(primaryHex, 0.0));
                                    return gradient;
                                }
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                align: 'end',
                                labels: {
                                    boxWidth: 8,
                                    boxHeight: 8,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    font: { family: fontFamily, size: 12, weight: '600' },
                                    color: textMuted,
                                    padding: 20
                                }
                            },
                            tooltip: {
                                backgroundColor: tooltipBg,
                                titleColor: textPrimary,
                                bodyColor: textPrimary,
                                footerColor: textMuted,
                                borderColor: borderColor,
                                borderWidth: 1,
                                titleFont: { family: fontFamily, size: 13, weight: '600' },
                                bodyFont: { family: fontFamily, size: 12 },
                                footerFont: { family: fontFamily, size: 12, weight: '700' },
                                padding: 12,
                                cornerRadius: 8,
                                boxPadding: 6,
                                callbacks: {
                                    label: (ctx) => {
                                        const val = ctx.parsed.y.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                        return ` ${ctx.dataset.label}: $${val}`;
                                    },
                                    footer: (tooltipItems) => {
                                        let total = 0;
                                        tooltipItems.forEach(item => total += item.parsed.y);
                                        const totalStr = total.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                        return `Total mes: $${totalStr}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: { font: { family: fontFamily, size: 11, weight: '500' }, color: textMuted }
                            },
                            y: {
                                grid: { color: borderLine, drawBorder: false },
                                border: { display: false, dash: [4, 4] },
                                ticks: {
                                    font: { family: fontFamily, size: 11 },
                                    color: textMuted,
                                    callback: (v) => {
                                        if (v >= 1000000) return `$${(v / 1000000).toFixed(1)}M`;
                                        if (v >= 1000) return `$${(v / 1000).toFixed(0)}k`;
                                        return `$${v}`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        };
    });
    </script>
    @endscript
</div>
