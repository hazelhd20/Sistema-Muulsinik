<div x-data="{ tab: @entangle('activeTab') }">
    @assets
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endassets

    {{-- Header --}}
    <x-page-header subtitle="Analítica" title="Reportes">
        <x-slot:actions>
            <x-custom-select wire:model.live="projectFilter" :options="$projects->pluck('name', 'id')->toArray()"
                placeholder="Todos los proyectos" class="w-auto min-w-[180px]" />
            <x-custom-select wire:model.live="period" :options="['week' => 'Última semana', 'month' => 'Último mes', 'quarter' => 'Último trimestre', 'year' => 'Último año', 'all' => 'Todo']" class="w-auto min-w-[140px]"
                placeholder="" />
        </x-slot:actions>
    </x-page-header>

    {{-- Tabs --}}
    <div class="tab-nav">
        <button @click="tab = 'overview'; $wire.set('activeTab', 'overview')"
            :class="tab === 'overview' ? 'active' : ''"
            class="tab-btn">
            Resumen
        </button>
        <button @click="tab = 'suppliers'; $wire.set('activeTab', 'suppliers')"
            :class="tab === 'suppliers' ? 'active' : ''"
            class="tab-btn">
            Proveedores
        </button>
        <button @click="tab = 'vendors'; $wire.set('activeTab', 'vendors')"
            :class="tab === 'vendors' ? 'active' : ''"
            class="tab-btn">
            Vendedores
        </button>
        <button @click="tab = 'products'; $wire.set('activeTab', 'products')"
            :class="tab === 'products' ? 'active' : ''"
            class="tab-btn">
            Productos
        </button>
    </div>

    {{-- Skeleton de Carga --}}
    <div wire:loading wire:target="activeTab" class="w-full">
        
        {{-- Skeleton Overview --}}
        <div x-show="tab === 'overview'" class="space-y-4">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                @for($i = 0; $i < 4; $i++)
                    <div class="card p-5 flex items-center gap-4">
                        <x-skeleton class="w-12 h-12 rounded-xl" />
                        <div class="flex-1">
                            <x-skeleton class="h-7 w-20 mb-2 rounded" />
                            <x-skeleton class="h-3 w-32 rounded" />
                        </div>
                    </div>
                @endfor
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 card p-6 flex flex-col h-64">
                    <div class="mb-4">
                        <x-skeleton class="h-4 w-40 mb-2 rounded" />
                        <x-skeleton class="h-3 w-24 rounded" />
                    </div>
                    <x-skeleton class="flex-1 w-full rounded-lg" />
                </div>
                <div class="card p-6 flex flex-col h-64">
                    <x-skeleton class="h-4 w-40 mb-4 rounded" />
                    <div class="flex-1 flex items-center justify-center">
                        <x-skeleton class="w-32 h-32 rounded-full" />
                    </div>
                </div>
            </div>
            {{-- Overview Bottom Cards (Presupuesto vs Top Proyectos) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
                <div class="card p-6 flex flex-col h-80">
                    <x-skeleton class="h-4 w-48 mb-6 rounded" />
                    <div class="space-y-6">
                        @for($i = 0; $i < 4; $i++)
                            <div>
                                <div class="flex justify-between mb-2">
                                    <x-skeleton class="h-3 w-32 rounded" />
                                    <x-skeleton class="h-3 w-16 rounded" />
                                </div>
                                <x-skeleton class="h-2 w-full rounded-full" />
                            </div>
                        @endfor
                    </div>
                </div>
                <div class="card p-6 flex flex-col h-80">
                    <x-skeleton class="h-4 w-40 mb-6 rounded" />
                    <div class="space-y-4">
                        @for($i = 0; $i < 5; $i++)
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <x-skeleton class="w-6 h-6 rounded" />
                                    <div>
                                        <x-skeleton class="h-4 w-32 mb-1 rounded" />
                                        <x-skeleton class="h-3 w-20 rounded" />
                                    </div>
                                </div>
                                <x-skeleton class="h-4 w-24 rounded" />
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>

        {{-- Skeleton Suppliers / Vendors --}}
        <div x-show="tab === 'suppliers' || tab === 'vendors'" style="display: none;" class="table-container min-h-[500px]">
            <div class="px-4 py-3 border-b border-border">
                <x-skeleton class="h-4 w-48 mb-2 rounded" />
                <x-skeleton class="h-3 w-64 rounded" />
            </div>
            <div class="overflow-x-auto flex-1">
                <table>
                    <thead>
                        <tr>
                            <th class="w-12"><x-skeleton class="h-4 w-4 rounded" /></th>
                            <th><x-skeleton class="h-4 w-32 rounded" /></th>
                            <th><x-skeleton class="h-4 w-24 rounded" /></th>
                            <th class="text-center"><x-skeleton class="h-4 w-16 mx-auto rounded" /></th>
                            <th class="text-right"><x-skeleton class="h-4 w-20 ml-auto rounded" /></th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 6; $i++)
                            <tr>
                                <td><x-skeleton class="h-6 w-6 rounded-lg" /></td>
                                <td><x-skeleton class="h-4 w-48 rounded" /></td>
                                <td><x-skeleton class="h-6 w-20 rounded-full" /></td>
                                <td class="text-center"><x-skeleton class="h-4 w-8 mx-auto rounded" /></td>
                                <td class="text-right"><x-skeleton class="h-4 w-24 ml-auto rounded" /></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Skeleton Products --}}
        <div x-show="tab === 'products'" style="display: none;" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 table-container min-h-[500px]">
                <div class="px-4 py-3 border-b border-border">
                    <x-skeleton class="h-4 w-48 mb-2 rounded" />
                    <x-skeleton class="h-3 w-64 rounded" />
                </div>
                <div class="overflow-x-auto flex-1">
                    <table>
                        <thead>
                            <tr>
                                <th class="w-12"><x-skeleton class="h-4 w-4 rounded" /></th>
                                <th><x-skeleton class="h-4 w-40 rounded" /></th>
                                <th><x-skeleton class="h-4 w-24 rounded" /></th>
                                <th class="text-right"><x-skeleton class="h-4 w-20 ml-auto rounded" /></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 6; $i++)
                                <tr>
                                    <td><x-skeleton class="h-6 w-6 rounded-lg" /></td>
                                    <td>
                                        <x-skeleton class="h-4 w-48 mb-1 rounded" />
                                        <x-skeleton class="h-3 w-16 rounded" />
                                    </td>
                                    <td><x-skeleton class="h-6 w-24 rounded-full" /></td>
                                    <td class="text-right"><x-skeleton class="h-4 w-24 ml-auto rounded" /></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card p-6 flex flex-col min-h-[500px]">
                <x-skeleton class="h-4 w-40 mb-4 rounded" />
                <div class="flex-1 flex items-center justify-center">
                    <x-skeleton class="w-48 h-48 rounded-full" />
                </div>
                <div class="mt-8 space-y-4">
                    @for($i = 0; $i < 5; $i++)
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <x-skeleton class="w-3 h-3 rounded-full" />
                                <x-skeleton class="w-24 h-3 rounded" />
                            </div>
                            <x-skeleton class="w-12 h-3 rounded" />
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    {{-- Contenido de Tabs --}}
    <div wire:loading.remove wire:target="activeTab">
        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- TAB: RESUMEN GENERAL --}}
        {{-- ═══════════════════════════════════════════════════ --}}
    @if($activeTab === 'overview')

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            <div class="stat-card">
                <div class="stat-icon bg-warning-light">
                    <x-lucide-dollar-sign class="w-5 h-5 text-warning" />
                </div>
                <div>
                    <p class="text-h2 font-bold text-text-primary tabular-nums">
                        ${{ number_format($totalExpenses, 0, '.', ',') }}</p>
                    <p class="text-xs-fluid text-text-muted">Gasto total del período</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-primary-50">
                    <x-lucide-receipt class="w-5 h-5 text-primary-600" />
                </div>
                <div>
                    <p class="text-h2 text-text-primary">{{ $expenseCount }}</p>
                    <p class="text-xs-fluid text-text-muted">Transacciones · ${{ number_format($avgExpense, 0, '.', ',') }}
                        prom.</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-success-light">
                    <x-lucide-check-circle class="w-5 h-5 text-success" />
                </div>
                <div>
                    <p class="text-h2 text-text-primary">{{ $requisitionsApproved }}</p>
                    <p class="text-xs-fluid text-text-muted">Requisiciones aprobadas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-danger-light">
                    <x-lucide-hard-hat class="w-5 h-5 text-danger" />
                </div>
                <div>
                    <p class="text-h2 text-text-primary">{{ $activeProjects }}/{{ $totalProjects }}</p>
                    <p class="text-xs-fluid text-text-muted">Proyectos activos</p>
                </div>
            </div>
        </div>

        {{-- Charts row --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
            {{-- Tendencia mensual --}}
            <div class="lg:col-span-2 card p-6 flex flex-col h-full">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-small font-semibold text-text-primary">Tendencia de Gastos</h2>
                        <p class="text-xs-fluid text-text-muted">Últimos 12 meses</p>
                    </div>
                </div>
                <div class="h-64 flex-1" data-chart="{{ json_encode($monthlyData) }}"
                     x-data="chartCanvas((data) => ({
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
                     }))">
                    <div wire:ignore class="h-full w-full">
                        <canvas></canvas>
                    </div>
                </div>
            </div>

            {{-- Distribución por categoría (donut) --}}
            <div class="card p-6 flex flex-col h-full">
                <h2 class="text-small font-semibold text-text-primary mb-4">Gastos por Categoría</h2>
                @if($expenseByCategory->isEmpty())
                    <div wire:key="overview-category-empty" class="flex-1 flex flex-col items-center justify-center min-h-[260px]">
                        <x-empty-state icon="pie-chart" title="Sin datos para el período" class="py-0" />
                    </div>
                @else
                    <div wire:key="overview-category-content" class="flex flex-col flex-1">
                        <div class="h-52 flex items-center justify-center" data-chart="{{ json_encode($expenseByCategory) }}"
                             x-data="chartCanvas((data) => {
                                 const labels = {{ json_encode($categoryLabels) }};
                                 const colors = ['#0230c8', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#6b7280'];
                                 return {
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
                                 };
                             })">
                            <div wire:ignore class="h-full w-full">
                                <canvas></canvas>
                            </div>
                        </div>
                        <div class="mt-4 space-y-2">
                            @php
                                $catColors = ['#0230c8', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#6b7280'];
                            @endphp
                            @foreach($expenseByCategory->take(5) as $i => $cat)
                                <div class="flex items-center justify-between text-body">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2.5 h-2.5 rounded-full" style="background: {{ $catColors[$i] ?? '#9ca3af' }}">
                                        </div>
                                        <span class="text-text-secondary">{{ $categoryLabels[$cat->category] ?? $cat->category }}</span>
                                    </div>
                                    <span class="font-medium text-text-primary">${{ number_format($cat->total, 0, '.', ',') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Presupuesto vs Gasto --}}
            <div class="card p-6 flex flex-col h-full">
                <h2 class="text-small font-semibold text-text-primary mb-4">Presupuesto vs Gasto Real</h2>
                @if($budgetComparison->isEmpty())
                    <div wire:key="overview-budget-empty" class="flex-1 flex flex-col items-center justify-center min-h-[200px]">
                        <x-empty-state icon="folder-open" title="Sin proyectos activos" class="py-0" />
                    </div>
                @else
                    <div wire:key="overview-budget-content" class="space-y-4 flex-1">
                        @foreach($budgetComparison as $comp)
                            @php
                                $p = min($comp['percent'], 100);
                                $barColor = $comp['percent'] >= 90 ? 'bg-danger' : ($comp['percent'] >= 70 ? 'bg-warning' : 'bg-primary-600');
                            @endphp
                            <div>
                                <div class="flex items-center justify-between text-body mb-1">
                                    <span class="font-medium text-text-primary truncate max-w-[60%]">{{ $comp['name'] }}</span>
                                    <span class="text-text-muted text-xs-fluid">
                                        ${{ number_format($comp['spent'], 0, '.', ',') }} /
                                        ${{ number_format($comp['budget'], 0, '.', ',') }}
                                    </span>
                                </div>
                                <div class="w-full h-2.5 bg-surface-main rounded-full overflow-hidden">
                                    <div class="{{ $barColor }} h-full rounded-full transition-all duration-500"
                                        style="width: {{ $p }}%"></div>
                                </div>
                                <div class="flex justify-between mt-0.5">
                                    <span class="text-xs-fluid text-text-muted">{{ $comp['percent'] }}% usado</span>
                                    @if($comp['percent'] >= 90)
                                        <span class="text-xs-fluid font-semibold text-danger">⚠ Sobrepresupuesto</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Top 5 proyectos por gasto --}}
            <div class="table-container flex flex-col h-full">
                <div class="px-4 py-3 border-b border-border">
                    <h2 class="text-small font-semibold text-text-primary">Top Proyectos por Gasto</h2>
                    <p class="text-xs-fluid text-text-muted">Período seleccionado</p>
                </div>
                @if($topProjects->isEmpty())
                    <div wire:key="overview-projects-empty" class="flex-1 flex flex-col items-center justify-center min-h-[200px]">
                        <x-empty-state icon="folder-open" title="Sin datos"
                            message="No hay proyectos en este período." class="py-0" />
                    </div>
                @else
                    <div wire:key="overview-projects-content" class="overflow-x-auto flex-1">
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
                                @foreach($topProjects as $i => $proj)
                                    <tr>
                                        <td>
                                            <span
                                                class="w-6 h-6 rounded-lg bg-surface-hover text-text-secondary border border-border text-xs-fluid font-bold flex items-center justify-center">
                                                {{ $i + 1 }}
                                            </span>
                                        </td>
                                        <td>
                                            <p class="font-medium">{{ $proj->name }}</p>
                                            <p class="text-xs-fluid text-text-muted">{{ $proj->client ?? '—' }}</p>
                                        </td>
                                        <td class="text-right text-body text-text-secondary">
                                            ${{ number_format($proj->budget, 0, '.', ',') }}</td>
                                        <td class="text-right text-body font-semibold text-text-primary">
                                            ${{ number_format($proj->total_spent, 0, '.', ',') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════ --}}
    {{-- TAB: PROVEEDORES --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    @if($activeTab === 'suppliers')
        <div class="table-container {{ $topSuppliers->isEmpty() ? 'flex flex-col min-h-[250px]' : '' }}">
            <div class="px-4 py-3 border-b border-border flex items-center justify-between">
                <div>
                    <h2 class="text-small font-semibold text-text-primary">Compras por Proveedor</h2>
                    <p class="text-xs-fluid text-text-muted">Monto total de requisiciones aprobadas en el período</p>
                </div>
            </div>
            @if($topSuppliers->isEmpty())
                <div wire:key="suppliers-table-empty" class="flex-1 flex flex-col items-center justify-center py-8">
                    <x-empty-state icon="building-2" title="Sin datos de proveedores"
                        message="No hay requisiciones aprobadas en este período." class="py-0" />
                </div>
            @else
                <div wire:key="suppliers-table-content" class="overflow-x-auto flex-1">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Proveedor</th>
                                <th>Categoría</th>
                                <th class="text-center">Requisiciones</th>
                                <th class="text-center">Productos</th>
                                <th class="text-right">Monto Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topSuppliers as $i => $supplier)
                                <tr>
                                    <td>
                                        <span
                                            class="w-6 h-6 rounded-lg bg-surface-hover text-text-secondary border border-border text-xs-fluid font-bold flex items-center justify-center">
                                            {{ $i + 1 }}
                                        </span>
                                    </td>
                                    <td class="font-medium text-text-primary">{{ $supplier->trade_name }}</td>
                                    <td>
                                        @if($supplier->category)
                                            <x-dynamic-badge :value="$supplier->category" />
                                        @else
                                            <span class="text-text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-body tabular-nums">{{ $supplier->total_requisitions }}</td>
                                    <td class="text-center text-body tabular-nums">{{ $supplier->total_items }}</td>
                                    <td class="text-right font-semibold text-text-primary tabular-nums">
                                        ${{ number_format($supplier->total_amount, 2, '.', ',') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════ --}}
    {{-- TAB: VENDEDORES --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    @if($activeTab === 'vendors')
        <div class="table-container {{ $topVendors->isEmpty() ? 'flex flex-col min-h-[250px]' : '' }}">
            <div class="px-4 py-3 border-b border-border flex items-center justify-between">
                <div>
                    <h2 class="text-small font-semibold text-text-primary">Compras por Vendedor</h2>
                    <p class="text-xs-fluid text-text-muted">Montos por vendedor/contacto de proveedor en el período</p>
                </div>
            </div>
            @if($topVendors->isEmpty())
                <div wire:key="vendors-table-empty" class="flex-1 flex flex-col items-center justify-center py-8">
                    <x-empty-state icon="user-check" title="Sin datos de vendedores"
                        message="No hay requisiciones aprobadas en este período." class="py-0" />
                </div>
            @else
                <div wire:key="vendors-table-content" class="overflow-x-auto flex-1">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Vendedor</th>
                                <th>Proveedor</th>
                                <th class="text-center">Requisiciones</th>
                                <th class="text-right">Monto Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topVendors as $i => $vendor)
                                <tr>
                                    <td>
                                        <span
                                            class="w-6 h-6 rounded-lg bg-surface-hover text-text-secondary border border-border text-xs-fluid font-bold flex items-center justify-center">
                                            {{ $i + 1 }}
                                        </span>
                                    </td>
                                    <td class="font-medium text-text-primary">{{ $vendor->vendor_name }}</td>
                                    <td class="text-body text-text-secondary">{{ $vendor->supplier_name }}</td>
                                    <td class="text-center text-body tabular-nums">{{ $vendor->total_requisitions }}</td>
                                    <td class="text-right font-semibold text-text-primary tabular-nums">
                                        ${{ number_format($vendor->total_amount, 2, '.', ',') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════ --}}
    {{-- TAB: PRODUCTOS MÁS COMPRADOS --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    @if($activeTab === 'products')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4 {{ $topProducts->isEmpty() ? 'items-stretch' : 'items-start' }}">
            {{-- Tabla de productos --}}
            <div class="lg:col-span-2 table-container flex flex-col {{ $topProducts->isEmpty() ? 'h-full' : '' }}">
                <div class="px-4 py-3 border-b border-border">
                    <h2 class="text-small font-semibold text-text-primary">Productos Más Comprados</h2>
                    <p class="text-xs-fluid text-text-muted">Top 15 por monto en requisiciones aprobadas</p>
                </div>
                @if($topProducts->isEmpty())
                    <div wire:key="products-table-empty" class="flex-1 flex flex-col items-center justify-center min-h-[250px]">
                        <x-empty-state icon="package" title="Sin datos de productos"
                            message="No hay requisiciones aprobadas en este período." class="py-0" />
                    </div>
                @else
                    <div wire:key="products-table-content" class="overflow-x-auto flex-1">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th class="text-center">Veces</th>
                                    <th class="text-center">Cant. Total</th>
                                    <th class="text-right">Precio Prom.</th>
                                    <th class="text-right">Monto Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topProducts as $i => $product)
                                    <tr>
                                        <td>
                                            <span
                                                class="w-6 h-6 rounded-lg bg-surface-hover text-text-secondary border border-border text-xs-fluid font-bold flex items-center justify-center">
                                                {{ $i + 1 }}
                                            </span>
                                        </td>
                                        <td>
                                            <p class="font-medium text-text-primary">{{ $product->canonical_name }}</p>
                                            @if($product->measure_abbr)
                                                <x-badge variant="secondary" class="text-[10px]">{{ $product->measure_abbr }}</x-badge>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->category_name)
                                                <x-dynamic-badge :value="$product->category_name" />
                                            @else
                                                <span class="text-text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center text-body tabular-nums">{{ $product->times_purchased }}</td>
                                        <td class="text-center text-body tabular-nums">
                                            {{ rtrim(rtrim(number_format($product->total_quantity, 2, '.', ','), '0'), '.') }}
                                        </td>
                                        <td class="text-right text-body text-text-secondary tabular-nums">
                                            ${{ number_format($product->avg_price, 2, '.', ',') }}</td>
                                        <td class="text-right font-semibold text-text-primary tabular-nums">
                                            ${{ number_format($product->total_amount, 2, '.', ',') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Donut por categoría de producto --}}
            <div class="card p-6 flex flex-col {{ $topProducts->isEmpty() ? 'h-full' : '' }}">
                <h2 class="text-small font-semibold text-text-primary mb-4">Compras por Categoría de Producto</h2>
                @if($topProducts->isEmpty() || $productsByCategory->isEmpty())
                    <div wire:key="products-chart-empty" class="flex-1 flex flex-col items-center justify-center min-h-[250px]">
                        <x-empty-state icon="pie-chart" title="Sin datos" class="py-0" />
                    </div>
                @else
                    <div wire:key="products-chart-content" class="flex flex-col flex-1">
                        <div class="h-52 flex items-center justify-center" data-chart="{{ json_encode($productsByCategory) }}"
                             x-data="chartCanvas((data) => {
                                 const colors = ['#0230c8', '#7c3aed', '#6366f1', '#06b6d4', '#ec4899', '#a855f7', '#8b5cf6', '#0ea5e9', '#64748b', '#78716c'];
                                 return {
                                     type: 'doughnut',
                                     data: {
                                         labels: data.map(d => d.category_name || 'Sin categoría'),
                                         datasets: [{
                                             data: data.map(d => d.total_amount),
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
                                 };
                             })">
                            <div wire:ignore class="h-full w-full">
                                <canvas></canvas>
                            </div>
                        </div>
                        <div class="mt-4 space-y-2">
                            @php
                                $pcColors = ['#0230c8', '#7c3aed', '#6366f1', '#06b6d4', '#ec4899', '#a855f7', '#8b5cf6', '#0ea5e9', '#64748b', '#78716c'];
                            @endphp
                            @foreach($productsByCategory->take(6) as $i => $pc)
                                <div class="flex items-center justify-between text-body">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2.5 h-2.5 rounded-full" style="background: {{ $pcColors[$i] ?? '#9ca3af' }}">
                                        </div>
                                        <span class="text-text-secondary">{{ $pc->category_name ?? '—' }}</span>
                                    </div>
                                    <span
                                        class="font-medium text-text-primary">${{ number_format($pc->total_amount, 0, '.', ',') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>

</div>
