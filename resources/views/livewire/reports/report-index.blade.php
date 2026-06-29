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
            <x-dropdown align="right" width="56">
                <x-slot:trigger>
                    <x-button variant="secondary" icon="download" iconRight="chevron-down" target="exportExcel, exportCsv">
                        Exportar
                    </x-button>
                </x-slot:trigger>
                <x-slot:content>
                    <x-dropdown-link as="button" wire:click="exportExcel" icon="file-spreadsheet">
                        Formato Excel (.xlsx)
                    </x-dropdown-link>
                    <x-dropdown-link as="button" wire:click="exportCsv" icon="file-text">
                        Formato CSV (.csv)
                    </x-dropdown-link>
                </x-slot:content>
            </x-dropdown>
        </x-slot:actions>
    </x-page-header>

    {{-- Tabs --}}
    <div class="tab-nav">
        <button @click="tab = 'overview'; $wire.set('activeTab', 'overview')"
            :class="tab === 'overview' ? 'active' : ''" class="tab-btn">
            Resumen
        </button>
        <button @click="tab = 'suppliers'; $wire.set('activeTab', 'suppliers')"
            :class="tab === 'suppliers' ? 'active' : ''" class="tab-btn">
            Proveedores
        </button>
        <button @click="tab = 'vendors'; $wire.set('activeTab', 'vendors')" :class="tab === 'vendors' ? 'active' : ''"
            class="tab-btn">
            Vendedores
        </button>
        <button @click="tab = 'products'; $wire.set('activeTab', 'products')"
            :class="tab === 'products' ? 'active' : ''" class="tab-btn">
            Productos
        </button>
    </div>

    {{-- Skeleton de Carga --}}
    <div wire:loading wire:target="activeTab" class="w-full">

        {{-- Skeleton Overview --}}
        <div x-show="tab === 'overview'" class="space-y-4">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                @for($i = 0; $i < 4; $i++)
                    <div class="bg-surface-card rounded-2xl border border-border p-5 shadow-sm">
                        <div class="flex items-start justify-between">
                            <div>
                                <x-skeleton class="h-3 w-24 mb-2 rounded" />
                                <x-skeleton class="h-8 w-32 rounded" />
                            </div>
                            <x-skeleton class="w-10 h-10 rounded-xl shrink-0" />
                        </div>
                    </div>
                @endfor
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                <x-card class="lg:col-span-2 p-6 flex flex-col h-full">
                    <div class="mb-4">
                        <x-skeleton class="h-4 w-40 mb-1.5 rounded" />
                        <x-skeleton class="h-3 w-24 rounded" />
                    </div>
                    <x-skeleton class="h-64 w-full rounded-lg" />
                </x-card>
                <x-card class="p-6 flex flex-col h-full">
                    <x-skeleton class="h-4 w-40 mb-4 rounded" />
                    <div class="h-52 flex items-center justify-center">
                        <x-skeleton class="w-40 h-40 rounded-full" />
                    </div>
                </x-card>
            </div>
            {{-- Overview Bottom Cards (Presupuesto vs Top Proyectos) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <x-card class="p-6 flex flex-col h-full">
                    <x-skeleton class="h-4 w-48 mb-6 rounded" />
                    <div class="space-y-6 flex-1">
                        @for($i = 0; $i < 4; $i++)
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <x-skeleton class="h-4 w-3/5 rounded" />
                                    <x-skeleton class="h-3 w-1/4 rounded" />
                                </div>
                                <x-skeleton class="h-2.5 w-full rounded-full" />
                                <div class="flex justify-between mt-1">
                                    <x-skeleton class="h-3 w-16 rounded" />
                                </div>
                            </div>
                        @endfor
                    </div>
                </x-card>
                <x-card class="flex flex-col h-full overflow-hidden">
                    <div class="px-6 pt-6 pb-4">
                        <x-skeleton class="h-4 w-40 rounded" />
                    </div>
                    <x-card.table class="flex-1 w-full">
                        <table class="w-full table-fixed min-w-[500px]">
                            <colgroup>
                                <col class="w-14">
                                <col class="w-[40%]">
                                <col class="w-[30%]">
                                <col class="w-[30%]">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th><x-skeleton class="h-4 w-4 rounded" /></th>
                                    <th><x-skeleton class="h-4 w-24 rounded" /></th>
                                    <th class="text-right"><x-skeleton class="h-4 w-20 ml-auto rounded" /></th>
                                    <th class="text-right"><x-skeleton class="h-4 w-16 ml-auto rounded" /></th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i = 0; $i < 5; $i++)
                                    <tr>
                                        <td><x-skeleton class="h-6 w-6 rounded-lg" /></td>
                                        <td>
                                            <x-skeleton class="h-4 w-32 mb-1.5 rounded" />
                                            <x-skeleton class="h-3 w-20 rounded" />
                                        </td>
                                        <td class="text-right"><x-skeleton class="h-4 w-16 ml-auto rounded" /></td>
                                        <td class="text-right"><x-skeleton class="h-4 w-16 ml-auto rounded" /></td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </x-card.table>
                </x-card>
            </div>
        </div>

        {{-- Skeleton Suppliers --}}
        <x-card x-show="tab === 'suppliers'" style="display: none;" class="flex flex-col min-h-[250px] overflow-hidden">
            <div class="px-6 pt-6 pb-4 flex items-center justify-between">
                <x-skeleton class="h-4 w-48 rounded" />
            </div>
            <x-card.table class="flex-1 w-full">
                <table class="w-full table-fixed min-w-[800px]">
                    <colgroup>
                        <col class="w-16">
                        <col class="w-[30%]">
                        <col class="w-[20%]">
                        <col class="w-24">
                        <col class="w-20">
                        <col class="w-[15%]">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="w-12"><x-skeleton class="h-4 w-4 rounded" /></th>
                            <th><x-skeleton class="h-4 w-32 rounded" /></th>
                            <th><x-skeleton class="h-4 w-24 rounded" /></th>
                            <th class="text-center"><x-skeleton class="h-4 w-16 mx-auto rounded" /></th>
                            <th class="text-center"><x-skeleton class="h-4 w-16 mx-auto rounded" /></th>
                            <th class="text-right"><x-skeleton class="h-4 w-24 ml-auto rounded" /></th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 6; $i++)
                            <tr>
                                <td><x-skeleton class="h-6 w-6 rounded-lg" /></td>
                                <td><x-skeleton class="h-4 w-48 rounded" /></td>
                                <td><x-skeleton class="h-6 w-20 rounded-full" /></td>
                                <td class="text-center"><x-skeleton class="h-4 w-8 mx-auto rounded" /></td>
                                <td class="text-center"><x-skeleton class="h-4 w-8 mx-auto rounded" /></td>
                                <td class="text-right"><x-skeleton class="h-4 w-20 ml-auto rounded" /></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </x-card.table>
        </x-card>

        {{-- Skeleton Vendors --}}
        <x-card x-show="tab === 'vendors'" style="display: none;" class="flex flex-col min-h-[250px] overflow-hidden">
            <div class="px-6 pt-6 pb-4 flex items-center justify-between">
                <x-skeleton class="h-4 w-48 rounded" />
            </div>
            <x-card.table class="flex-1 w-full">
                <table class="w-full table-fixed min-w-[800px]">
                    <colgroup>
                        <col class="w-16">
                        <col class="w-[30%]">
                        <col class="w-[30%]">
                        <col class="w-28">
                        <col class="w-28">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="w-12"><x-skeleton class="h-4 w-4 rounded" /></th>
                            <th><x-skeleton class="h-4 w-32 rounded" /></th>
                            <th><x-skeleton class="h-4 w-32 rounded" /></th>
                            <th class="text-center"><x-skeleton class="h-4 w-16 mx-auto rounded" /></th>
                            <th class="text-right"><x-skeleton class="h-4 w-24 ml-auto rounded" /></th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 6; $i++)
                            <tr>
                                <td><x-skeleton class="h-6 w-6 rounded-lg" /></td>
                                <td><x-skeleton class="h-4 w-40 rounded" /></td>
                                <td><x-skeleton class="h-4 w-40 rounded" /></td>
                                <td class="text-center"><x-skeleton class="h-4 w-8 mx-auto rounded" /></td>
                                <td class="text-right"><x-skeleton class="h-4 w-20 ml-auto rounded" /></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </x-card.table>
        </x-card>

        {{-- Skeleton Products --}}
        <div x-show="tab === 'products'" style="display: none;"
            class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
            <x-card class="lg:col-span-2 flex flex-col min-h-[250px] overflow-hidden">
                <div class="px-6 pt-6 pb-4">
                    <x-skeleton class="h-4 w-48 rounded" />
                </div>
                <x-card.table class="flex-1 w-full">
                    <table class="w-full table-fixed min-w-[700px]">
                        <colgroup>
                            <col class="w-16">
                            <col class="w-[45%]">
                            <col class="w-32">
                            <col class="w-24">
                            <col class="w-24">
                            <col class="w-28">
                            <col class="w-28">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="w-12"><x-skeleton class="h-4 w-4 rounded" /></th>
                                <th><x-skeleton class="h-4 w-32 rounded" /></th>
                                <th><x-skeleton class="h-4 w-24 rounded" /></th>
                                <th class="text-center"><x-skeleton class="h-4 w-16 mx-auto rounded" /></th>
                                <th class="text-center"><x-skeleton class="h-4 w-16 mx-auto rounded" /></th>
                                <th class="text-right"><x-skeleton class="h-4 w-20 ml-auto rounded" /></th>
                                <th class="text-right"><x-skeleton class="h-4 w-24 ml-auto rounded" /></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 6; $i++)
                                <tr>
                                    <td><x-skeleton class="h-6 w-6 rounded-lg" /></td>
                                    <td>
                                        <x-skeleton class="h-4 w-48 mb-1.5 rounded" />
                                        <x-skeleton class="h-3 w-16 rounded" />
                                    </td>
                                    <td><x-skeleton class="h-6 w-24 rounded-full" /></td>
                                    <td class="text-center"><x-skeleton class="h-4 w-8 mx-auto rounded" /></td>
                                    <td class="text-center"><x-skeleton class="h-4 w-12 mx-auto rounded" /></td>
                                    <td class="text-right"><x-skeleton class="h-4 w-16 ml-auto rounded" /></td>
                                    <td class="text-right"><x-skeleton class="h-4 w-20 ml-auto rounded" /></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </x-card.table>
            </x-card>
            <x-card class="p-6 flex flex-col h-full">
                <x-skeleton class="h-4 w-48 mb-4 rounded" />
                <div class="flex-1 flex items-center justify-center min-h-[200px]">
                    <x-skeleton class="w-48 h-48 rounded-full" />
                </div>
                <div class="mt-8 space-y-4">
                    @for($i = 0; $i < 5; $i++)
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <x-skeleton class="w-3 h-3 rounded-full" />
                                <x-skeleton class="w-32 h-3 rounded" />
                            </div>
                            <x-skeleton class="w-16 h-4 rounded" />
                        </div>
                    @endfor
                </div>
            </x-card>
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
                <x-stat-card title="Gasto total del período" value="${{ number_format($totalExpenses, 0, '.', ',') }}"
                    icon="dollar-sign" color="warning" />

                <x-stat-card title="Transacciones" value="{{ $expenseCount }}" icon="receipt" color="primary"
                    footer="Promedio: ${{ number_format($avgExpense, 0, '.', ',') }}" />

                <x-stat-card title="Requisiciones aprobadas" value="{{ $requisitionsApproved }}" icon="check-circle"
                    color="success" />

                <x-stat-card title="Proyectos activos" value="{{ $activeProjects }}/{{ $totalProjects }}" icon="hard-hat"
                    color="danger" />
            </div>

            {{-- Charts row --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                {{-- Tendencia mensual --}}
                <x-card class="lg:col-span-2 p-6 flex flex-col h-full">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-small font-semibold text-text-primary">Tendencia de Gastos</h2>
                            <p class="text-xs text-text-muted">Últimos 12 meses</p>
                        </div>
                    </div>
                    @if(collect($monthlyData)->sum('total') == 0)
                        <div wire:key="overview-monthly-empty"
                            class="flex-1 flex flex-col items-center justify-center min-h-[256px]">
                            <x-empty-state icon="trending-up" title="Sin gastos registrados"
                                message="No se han registrado gastos en los últimos 12 meses." class="py-0" />
                        </div>
                    @else
                        <div wire:key="overview-monthly-content" class="h-64 flex-1"
                            data-chart="{{ json_encode($monthlyData) }}" x-data="chartCanvas((data) => {
                                     const style = getComputedStyle(document.documentElement);
                                     const primaryHex = style.getPropertyValue('--color-primary-600').trim() || '#2563eb';
                                     const textPrimary = style.getPropertyValue('--color-text-primary').trim() || '#0f1117';
                                     const textMuted = style.getPropertyValue('--color-text-muted').trim() || '#475569';
                                     const borderLight = style.getPropertyValue('--color-border-light').trim() || 'rgba(0,0,0,0.04)';

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

                                     return {
                                         type: 'line',
                                         data: {
                                             labels: data.map(d => d.short),
                                             datasets: [{
                                                 label: 'Gastos',
                                                 data: data.map(d => d.total),
                                                 borderColor: primaryHex,
                                                 backgroundColor: (context) => {
                                                     const chart = context.chart;
                                                     const {ctx, chartArea} = chart;
                                                     if (!chartArea) return hexToRgba(primaryHex, 0.08);
                                                     const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                                                     gradient.addColorStop(0, hexToRgba(primaryHex, 0.22));
                                                     gradient.addColorStop(1, hexToRgba(primaryHex, 0.0));
                                                     return gradient;
                                                 },
                                                 fill: true,
                                                 tension: 0.35,
                                                 borderWidth: 3,
                                                 pointBackgroundColor: '#ffffff',
                                                 pointBorderColor: primaryHex,
                                                 pointBorderWidth: 2,
                                                 pointRadius: 4,
                                                 pointHoverRadius: 7,
                                                 pointHoverBackgroundColor: primaryHex,
                                                 pointHoverBorderColor: '#ffffff',
                                                 pointHoverBorderWidth: 2,
                                             }]
                                         },
                                         options: {
                                             responsive: true,
                                             maintainAspectRatio: false,
                                             plugins: {
                                                 legend: { display: false },
                                                 tooltip: {
                                                     backgroundColor: textPrimary,
                                                     padding: 12,
                                                     cornerRadius: 10,
                                                     boxPadding: 6,
                                                     usePointStyle: true,
                                                     titleFont: { family: 'Plus Jakarta Sans', size: 13, weight: '600' },
                                                     bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
                                                     callbacks: { label: ctx => ` Gastos: $${Number(ctx.parsed.y).toLocaleString()}` }
                                                 }
                                             },
                                             scales: {
                                                 x: {
                                                     grid: { display: false },
                                                     ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, color: textMuted }
                                                 },
                                                 y: {
                                                     grid: { color: borderLight },
                                                     ticks: {
                                                         font: { family: 'Plus Jakarta Sans', size: 11 },
                                                         color: textMuted,
                                                         callback: v => v >= 1000 ? `$${(v / 1000).toFixed(0)}k` : `$${v}`
                                                     }
                                                 }
                                             }
                                         }
                                     };
                                 })">
                            <div wire:ignore class="h-full w-full">
                                <canvas></canvas>
                            </div>
                        </div>
                    @endif
                </x-card>

                {{-- Distribución por categoría (donut) --}}
                <x-card class="p-6 flex flex-col h-full">
                    <h2 class="text-small font-semibold text-text-primary mb-4">Gastos por Categoría</h2>
                    @if($expenseByCategory->isEmpty())
                        <div wire:key="overview-category-empty"
                            class="flex-1 flex flex-col items-center justify-center min-h-[260px]">
                            <x-empty-state icon="pie-chart" title="No se encontraron gastos"
                                message="No hay registros para el período seleccionado." class="py-0" />
                        </div>
                    @else
                        <div wire:key="overview-category-content" class="flex flex-col flex-1">
                            <div class="h-52 flex items-center justify-center"
                                data-chart="{{ json_encode($expenseByCategory) }}" x-data="chartCanvas((data) => {
                                         const labels = {{ json_encode($categoryLabels) }};
                                         const style = getComputedStyle(document.documentElement);
                                         const textPrimary = style.getPropertyValue('--color-text-primary').trim() || '#0f1117';
                                         const cardBg = style.getPropertyValue('--color-surface-card').trim() || '#ffffff';
                                         const colors = [
                                             style.getPropertyValue('--color-chart-1').trim() || '#2563eb',
                                             style.getPropertyValue('--color-chart-2').trim() || '#0284c7',
                                             style.getPropertyValue('--color-chart-3').trim() || '#0d9e6e',
                                             style.getPropertyValue('--color-chart-4').trim() || '#d97706',
                                             style.getPropertyValue('--color-chart-5').trim() || '#dc2626',
                                             style.getPropertyValue('--color-chart-6').trim() || '#7c3aed',
                                             style.getPropertyValue('--color-chart-7').trim() || '#db2777'
                                         ];
                                         return {
                                             type: 'doughnut',
                                             data: {
                                                 labels: data.map(d => labels[d.category] || d.category),
                                                 datasets: [{
                                                     data: data.map(d => d.total),
                                                     backgroundColor: colors.slice(0, data.length),
                                                     borderWidth: 2,
                                                     borderColor: cardBg,
                                                     hoverOffset: 6,
                                                 }]
                                             },
                                             options: {
                                                 responsive: true,
                                                 maintainAspectRatio: false,
                                                 cutout: '72%',
                                                 plugins: {
                                                     legend: { display: false },
                                                     tooltip: {
                                                         backgroundColor: textPrimary,
                                                         padding: 10,
                                                         cornerRadius: 8,
                                                         usePointStyle: true,
                                                         titleFont: { family: 'Plus Jakarta Sans', weight: '600' },
                                                         bodyFont: { family: 'Plus Jakarta Sans' },
                                                         callbacks: {
                                                             label: (ctx) => {
                                                                 const total = ctx.dataset.data.reduce((acc, val) => acc + Number(val), 0);
                                                                 const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) + '%' : '0%';
                                                                 return ` ${ctx.label}: $${Number(ctx.parsed).toLocaleString()} (${pct})`;
                                                             }
                                                         }
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
                                    $catColors = ['var(--color-chart-1)', 'var(--color-chart-2)', 'var(--color-chart-3)', 'var(--color-chart-4)', 'var(--color-chart-5)', 'var(--color-chart-6)', 'var(--color-chart-7)'];
                                @endphp
                                @foreach($expenseByCategory->take(5) as $i => $cat)
                                    <div class="flex items-center justify-between text-body">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2.5 h-2.5 rounded-full"
                                                style="background: {{ $catColors[$i] ?? 'var(--color-chart-10)' }}">
                                            </div>
                                            <span
                                                class="text-text-secondary">{{ $categoryLabels[$cat->category] ?? $cat->category }}</span>
                                        </div>
                                        <span
                                            class="font-medium text-text-primary">${{ number_format($cat->total, 0, '.', ',') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </x-card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {{-- Presupuesto vs Gasto --}}
                <x-card class="p-6 flex flex-col h-full">
                    <h2 class="text-small font-semibold text-text-primary mb-4">Presupuesto vs Gasto Real</h2>
                    @if($budgetComparison->isEmpty())
                        <div wire:key="overview-budget-empty"
                            class="flex-1 flex flex-col items-center justify-center min-h-[200px]">
                            <x-empty-state icon="folder-open" title="No se encontraron proyectos"
                                message="No hay registros para el período seleccionado." class="py-0" />
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
                                        <span class="text-text-muted text-xs">
                                            ${{ number_format($comp['spent'], 0, '.', ',') }} /
                                            ${{ number_format($comp['budget'], 0, '.', ',') }}
                                        </span>
                                    </div>
                                    <div class="w-full h-2.5 bg-surface-main rounded-full overflow-hidden">
                                        <div class="{{ $barColor }} h-full rounded-full transition-all duration-500"
                                            style="width: {{ $p }}%"></div>
                                    </div>
                                    <div class="flex justify-between mt-0.5">
                                        <span class="text-xs text-text-muted">{{ $comp['percent'] }}% usado</span>
                                        @if($comp['percent'] >= 90)
                                            <span class="text-xs font-semibold text-danger">⚠ Sobrepresupuesto</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                {{-- Top 5 proyectos por gasto --}}
                <x-card class="flex flex-col h-full overflow-hidden">
                    <div class="px-6 pt-6 pb-4">
                        <h2 class="text-small font-semibold text-text-primary">Top Proyectos por Gasto</h2>
                    </div>
                    @if($topProjects->isEmpty())
                        <div wire:key="overview-projects-empty"
                            class="flex-1 flex flex-col items-center justify-center min-h-[200px]">
                            <x-empty-state icon="folder-open" title="No se encontraron proyectos"
                                message="No hay registros para el período seleccionado." class="py-0" />
                        </div>
                    @else
                        <x-card.table wire:key="overview-projects-content" class="flex-1 w-full">
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
                                                    class="w-6 h-6 rounded-lg bg-surface-hover text-text-secondary border border-border text-xs font-bold flex items-center justify-center">
                                                    {{ $i + 1 }}
                                                </span>
                                            </td>
                                            <td>
                                                <p class="font-medium">{{ $proj->name }}</p>
                                                <p class="text-xs text-text-muted">{{ $proj->client?->name ?? '—' }}</p>
                                            </td>
                                            <td class="text-right text-body text-text-secondary">
                                                ${{ number_format($proj->budget, 0, '.', ',') }}</td>
                                            <td class="text-right text-body font-semibold text-text-primary">
                                                ${{ number_format($proj->total_spent, 0, '.', ',') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </x-card.table>
                    @endif
                </x-card>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- TAB: PROVEEDORES --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        @if($activeTab === 'suppliers')
            <x-card class="{{ $topSuppliers->isEmpty() ? 'flex flex-col min-h-[250px]' : '' }} overflow-hidden">
                <div class="px-6 pt-6 pb-4 flex items-center justify-between">
                    <h2 class="text-small font-semibold text-text-primary">Compras por Proveedor</h2>
                </div>
                @if($topSuppliers->isEmpty())
                    <div wire:key="suppliers-table-empty" class="flex-1 flex flex-col items-center justify-center py-8">
                        <x-empty-state icon="building-2" title="No se encontraron proveedores"
                            message="No hay registros para el período seleccionado." class="py-0" />
                    </div>
                @else
                    <x-card.table wire:key="suppliers-table-content" class="flex-1 w-full">
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
                                                class="w-6 h-6 rounded-lg bg-surface-hover text-text-secondary border border-border text-xs font-bold flex items-center justify-center">
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
                    </x-card.table>
                @endif
            </x-card>
        @endif

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- TAB: VENDEDORES --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        @if($activeTab === 'vendors')
            <x-card class="{{ $topVendors->isEmpty() ? 'flex flex-col min-h-[250px]' : '' }} overflow-hidden">
                <div class="px-6 pt-6 pb-4 flex items-center justify-between">
                    <h2 class="text-small font-semibold text-text-primary">Compras por Vendedor</h2>
                </div>
                @if($topVendors->isEmpty())
                    <div wire:key="vendors-table-empty" class="flex-1 flex flex-col items-center justify-center py-8">
                        <x-empty-state icon="user-check" title="No se encontraron vendedores"
                            message="No hay registros para el período seleccionado." class="py-0" />
                    </div>
                @else
                    <x-card.table wire:key="vendors-table-content" class="flex-1 w-full">
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
                                                class="w-6 h-6 rounded-lg bg-surface-hover text-text-secondary border border-border text-xs font-bold flex items-center justify-center">
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
                    </x-card.table>
                @endif
            </x-card>
        @endif

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- TAB: PRODUCTOS MÁS COMPRADOS --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        @if($activeTab === 'products')
            <div
                class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4 {{ $topProducts->isEmpty() ? 'items-stretch' : 'items-start' }}">
                {{-- Tabla de productos --}}
                <x-card class="lg:col-span-2 flex flex-col {{ $topProducts->isEmpty() ? 'h-full' : '' }} overflow-hidden">
                    <div class="px-6 pt-6 pb-4">
                        <h2 class="text-small font-semibold text-text-primary">Productos Más Comprados</h2>
                    </div>
                    @if($topProducts->isEmpty())
                        <div wire:key="products-table-empty"
                            class="flex-1 flex flex-col items-center justify-center min-h-[250px]">
                            <x-empty-state icon="package" title="No se encontraron productos"
                                message="No hay registros para el período seleccionado." class="py-0" />
                        </div>
                    @else
                        <x-card.table wire:key="products-table-content" class="flex-1 w-full">
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
                                                    class="w-6 h-6 rounded-lg bg-surface-hover text-text-secondary border border-border text-xs font-bold flex items-center justify-center">
                                                    {{ $i + 1 }}
                                                </span>
                                            </td>
                                            <td>
                                                <p class="font-medium text-text-primary">{{ $product->canonical_name }}</p>
                                                @if($product->measure_abbr)
                                                    <x-badge variant="secondary"
                                                        class="text-[10px]">{{ $product->measure_abbr }}</x-badge>
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
                        </x-card.table>
                    @endif
                </x-card>

                {{-- Donut por categoría de producto --}}
                <x-card class="p-6 flex flex-col {{ $topProducts->isEmpty() ? 'h-full' : '' }}">
                    <h2 class="text-small font-semibold text-text-primary mb-4">Compras por Categoría de Producto</h2>
                    @if($topProducts->isEmpty() || $productsByCategory->isEmpty())
                        <div wire:key="products-chart-empty"
                            class="flex-1 flex flex-col items-center justify-center min-h-[250px]">
                            <x-empty-state icon="pie-chart" title="No se encontraron productos"
                                message="No hay registros para el período seleccionado." class="py-0" />
                        </div>
                    @else
                        <div wire:key="products-chart-content" class="flex flex-col flex-1">
                            <div class="h-52 flex items-center justify-center"
                                data-chart="{{ json_encode($productsByCategory) }}" x-data="chartCanvas((data) => {
                                         const style = getComputedStyle(document.documentElement);
                                         const textPrimary = style.getPropertyValue('--color-text-primary').trim() || '#0f1117';
                                         const cardBg = style.getPropertyValue('--color-surface-card').trim() || '#ffffff';
                                         const colors = [
                                             style.getPropertyValue('--color-chart-1').trim() || '#2563eb',
                                             style.getPropertyValue('--color-chart-2').trim() || '#0284c7',
                                             style.getPropertyValue('--color-chart-3').trim() || '#0d9e6e',
                                             style.getPropertyValue('--color-chart-4').trim() || '#d97706',
                                             style.getPropertyValue('--color-chart-5').trim() || '#dc2626',
                                             style.getPropertyValue('--color-chart-6').trim() || '#7c3aed',
                                             style.getPropertyValue('--color-chart-7').trim() || '#db2777',
                                             style.getPropertyValue('--color-chart-8').trim() || '#0891b2',
                                             style.getPropertyValue('--color-chart-9').trim() || '#ea580c',
                                             style.getPropertyValue('--color-chart-10').trim() || '#64748b'
                                         ];
                                         return {
                                             type: 'doughnut',
                                             data: {
                                                 labels: data.map(d => d.category_name || 'Sin categoría'),
                                                 datasets: [{
                                                     data: data.map(d => d.total_amount),
                                                     backgroundColor: colors.slice(0, data.length),
                                                     borderWidth: 2,
                                                     borderColor: cardBg,
                                                     hoverOffset: 6,
                                                 }]
                                             },
                                             options: {
                                                 responsive: true,
                                                 maintainAspectRatio: false,
                                                 cutout: '72%',
                                                 plugins: {
                                                     legend: { display: false },
                                                     tooltip: {
                                                         backgroundColor: textPrimary,
                                                         padding: 10,
                                                         cornerRadius: 8,
                                                         usePointStyle: true,
                                                         titleFont: { family: 'Plus Jakarta Sans', weight: '600' },
                                                         bodyFont: { family: 'Plus Jakarta Sans' },
                                                         callbacks: {
                                                             label: (ctx) => {
                                                                 const total = ctx.dataset.data.reduce((acc, val) => acc + Number(val), 0);
                                                                 const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) + '%' : '0%';
                                                                 return ` ${ctx.label}: $${Number(ctx.parsed).toLocaleString()} (${pct})`;
                                                             }
                                                         }
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
                                    $pcColors = ['var(--color-chart-1)', 'var(--color-chart-2)', 'var(--color-chart-3)', 'var(--color-chart-4)', 'var(--color-chart-5)', 'var(--color-chart-6)', 'var(--color-chart-7)', 'var(--color-chart-8)', 'var(--color-chart-9)', 'var(--color-chart-10)'];
                                @endphp
                                @foreach($productsByCategory->take(6) as $i => $pc)
                                    <div class="flex items-center justify-between text-body">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2.5 h-2.5 rounded-full"
                                                style="background: {{ $pcColors[$i] ?? 'var(--color-chart-10)' }}">
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
                </x-card>
            </div>
        @endif
    </div>

</div>