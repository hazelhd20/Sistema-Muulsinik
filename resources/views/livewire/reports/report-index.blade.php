<div>
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
    <div class="flex items-center gap-1 mb-5 border-b border-border" x-data="{ tab: @entangle('activeTab') }">
        <button @click="tab = 'overview'; $wire.set('activeTab', 'overview')"
            :class="tab === 'overview' ? 'border-primary-600 text-primary-700 font-semibold' : 'border-transparent text-text-muted hover:text-text-secondary'"
            class="px-4 py-2.5 text-small border-b-2 transition-colors flex items-center gap-1.5">
            <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i> Resumen
        </button>
        <button @click="tab = 'suppliers'; $wire.set('activeTab', 'suppliers')"
            :class="tab === 'suppliers' ? 'border-primary-600 text-primary-700 font-semibold' : 'border-transparent text-text-muted hover:text-text-secondary'"
            class="px-4 py-2.5 text-small border-b-2 transition-colors flex items-center gap-1.5">
            <i data-lucide="building-2" class="w-3.5 h-3.5"></i> Proveedores
        </button>
        <button @click="tab = 'vendors'; $wire.set('activeTab', 'vendors')"
            :class="tab === 'vendors' ? 'border-primary-600 text-primary-700 font-semibold' : 'border-transparent text-text-muted hover:text-text-secondary'"
            class="px-4 py-2.5 text-small border-b-2 transition-colors flex items-center gap-1.5">
            <i data-lucide="user-check" class="w-3.5 h-3.5"></i> Vendedores
        </button>
        <button @click="tab = 'products'; $wire.set('activeTab', 'products')"
            :class="tab === 'products' ? 'border-primary-600 text-primary-700 font-semibold' : 'border-transparent text-text-muted hover:text-text-secondary'"
            class="px-4 py-2.5 text-small border-b-2 transition-colors flex items-center gap-1.5">
            <i data-lucide="package" class="w-3.5 h-3.5"></i> Productos
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════════ --}}
    {{-- TAB: RESUMEN GENERAL --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    @if($activeTab === 'overview')

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            <div class="stat-card">
                <div class="stat-icon bg-warning-light">
                    <i data-lucide="dollar-sign" class="w-5 h-5 text-warning"></i>
                </div>
                <div>
                    <p class="text-h2 font-bold text-text-primary tabular-nums">
                        ${{ number_format($totalExpenses, 0, '.', ',') }}</p>
                    <p class="text-xs-fluid text-text-muted">Gasto total del período</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-primary-50">
                    <i data-lucide="receipt" class="w-5 h-5 text-primary-600"></i>
                </div>
                <div>
                    <p class="text-h2 text-text-primary">{{ $expenseCount }}</p>
                    <p class="text-xs-fluid text-text-muted">Transacciones · ${{ number_format($avgExpense, 0, '.', ',') }}
                        prom.</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-success-light">
                    <i data-lucide="check-circle" class="w-5 h-5 text-success"></i>
                </div>
                <div>
                    <p class="text-h2 text-text-primary">{{ $requisitionsApproved }}</p>
                    <p class="text-xs-fluid text-text-muted">Requisiciones aprobadas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-danger-light">
                    <i data-lucide="hard-hat" class="w-5 h-5 text-danger"></i>
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
            <div class="lg:col-span-2 card flex flex-col h-full">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-small font-semibold text-text-primary">Tendencia de Gastos</h2>
                        <p class="text-xs-fluid text-text-muted">Últimos 12 meses</p>
                    </div>
                </div>
                <div class="h-64 flex-1" wire:ignore x-data="trendChart()" x-init="init()">
                    <canvas id="trend-chart"></canvas>
                </div>
            </div>

            {{-- Distribución por categoría (donut) --}}
            <div class="card flex flex-col h-full">
                <h2 class="text-small font-semibold text-text-primary mb-4">Gastos por Categoría</h2>
                @if($expenseByCategory->isEmpty())
                    <div wire:key="overview-category-empty" class="flex-1 flex flex-col items-center justify-center min-h-[260px]">
                        <x-empty-state icon="pie-chart" title="Sin datos para el período" class="py-0" />
                    </div>
                @else
                    <div wire:key="overview-category-content" class="flex flex-col flex-1">
                        <div class="h-52 flex items-center justify-center" wire:ignore x-data="categoryChart()" x-init="init()">
                            <canvas id="category-chart"></canvas>
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
            <div class="card flex flex-col h-full">
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
                                    <th class="text-right">Cant. Total</th>
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
                                                <span class="badge badge-secondary text-[10px]">{{ $product->measure_abbr }}</span>
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
                                        <td class="text-right text-body tabular-nums">
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
            <div class="card flex flex-col {{ $topProducts->isEmpty() ? 'h-full' : '' }}">
                <h2 class="text-small font-semibold text-text-primary mb-4">Compras por Categoría de Producto</h2>
                @if($topProducts->isEmpty() || $productsByCategory->isEmpty())
                    <div wire:key="products-chart-empty" class="flex-1 flex flex-col items-center justify-center min-h-[250px]">
                        <x-empty-state icon="pie-chart" title="Sin datos" class="py-0" />
                    </div>
                @else
                    <div wire:key="products-chart-content" class="flex flex-col flex-1">
                        <div class="h-52 flex items-center justify-center" wire:ignore x-data="productCategoryChart()" x-init="init()">
                            <canvas id="product-category-chart"></canvas>
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

    Alpine.data('productCategoryChart', () => ({
        init() {
            const ctx = document.getElementById('product-category-chart');
            if (!ctx) return;
            const data = @json($productsByCategory);
            const colors = ['#0230c8', '#7c3aed', '#6366f1', '#06b6d4', '#ec4899', '#a855f7', '#8b5cf6', '#0ea5e9', '#64748b', '#78716c'];
            new Chart(ctx, {
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
            });
        }
    }));
</script>
@endscript