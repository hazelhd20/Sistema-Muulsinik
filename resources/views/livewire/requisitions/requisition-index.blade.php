<div x-data="{ tab: @entangle('tab').live }" x-on:livewire:navigated.window="tab = $wire.tab">
    <div>
        {{-- Header --}}
        <x-page-header subtitle="Compras" title="Requisiciones" icon="clipboard-list">
            <x-slot:actions>
                <x-button href="{{ route('requisiciones.manual', ['source' => $tab]) }}" variant="secondary" icon="plus"
                    wire:navigate>
                    Nueva Manual
                </x-button>
                <x-button href="{{ route('requisiciones.upload', ['source' => $tab]) }}" variant="primary"
                    icon="scan-line" wire:navigate>
                    Subir Cotización
                </x-button>
            </x-slot:actions>
        </x-page-header>

        {{-- Tabs de Navegación --}}
        <div class="tab-nav mb-6">
            <button @click="tab = 'todas'" :class="tab === 'todas' ? 'active' : ''" class="tab-btn">
                Requisiciones
            </button>
            <button @click="tab = 'borradores'" :class="tab === 'borradores' ? 'active' : ''" class="tab-btn">
                Borradores y Procesos
                @if($pendingQuotations->count() > 0)
                    <span class="count-badge">{{ $pendingQuotations->count() }}</span>
                @endif
            </button>
        </div>

        <div x-show="tab === 'borradores'" x-cloak wire:key="tab-borradores">
            <livewire:requisitions.pending-quotations-list />
        </div>

        <div x-show="tab === 'todas'" x-cloak wire:key="tab-todas-table" 
             x-data="requisitionIndex(@entangle('selectedRows'), {{ $requisitions->mapWithKeys(fn($r) => [$r->id => $r->status])->toJson() }})"
             x-init="totalOnPage = {{ $requisitions->count() }}; init()">
            {{-- Unified Datagrid Card Container --}}
            <div
                class="mt-0 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-xl md:shadow-sm">
                @php
                    $activeCount = ($statusFilter ? 1 : 0) + ($projectFilter ? 1 : 0) + ($periodFilter ? 1 : 0) + ($creatorFilter ? 1 : 0) + ($vendorFilter ? 1 : 0);
                    $hasActiveFilters = !empty($search) || $activeCount > 0;
                @endphp

                @if($requisitions->isNotEmpty() || $hasActiveFilters)
                    {{-- Header Group (Search + Filters + Chips) --}}
                    <div class="card md:rounded-t-xl md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                        {{-- Filters Bar --}}
                        <div class="flex flex-row gap-3 items-center justify-between w-full p-4 md:px-6 md:py-4">
                            {{-- Search: compact width --}}
                            <div class="flex-1 min-w-0">
                                <x-search-input wire:model.live.debounce.300ms="search"
                                    placeholder="Buscar requisición..." />
                            </div>

                            {{-- Filters Popover --}}
                            <x-filters-popover :activeCount="$activeCount" :columns="2" @filters-opened="initFilters()">
                                <x-form-field label="Estado">
                                    <x-custom-select x-model="filterStatus" :options="['borrador' => 'Borrador', 'pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada']"
                                        placeholder="Todos los estados" />
                                </x-form-field>

                                <x-form-field label="Proyecto">
                                    <x-custom-select x-model="filterProject" :options="$projects"
                                        placeholder="Todos los proyectos" />
                                </x-form-field>

                                <x-form-field label="Creador">
                                    <x-custom-select x-model="filterCreator" :options="$creators"
                                        placeholder="Todos los creadores" />
                                </x-form-field>

                                <x-form-field label="Proveedor">
                                    <x-custom-select x-model="filterVendor" :options="$vendors"
                                        placeholder="Todos los proveedores" />
                                </x-form-field>

                                <x-form-field label="Período">
                                    <x-custom-select x-model="filterPeriod" :options="['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año', 'custom' => 'Rango personalizado']" placeholder="Todos los períodos" />
                                </x-form-field>

                                <div x-show="filterPeriod === 'custom'" x-collapse class="col-span-full mt-2">
                                    <div class="grid grid-cols-2 gap-4">
                                        <x-form-field label="Desde">
                                            <x-date-picker x-model="filterDateFrom" :options="['maxDate' => 'today']"
                                                placeholder="Fecha inicio" />
                                        </x-form-field>
                                        <x-form-field label="Hasta">
                                            <x-date-picker x-model="filterDateTo" :options="['maxDate' => 'today']"
                                                placeholder="Fecha fin" />
                                        </x-form-field>
                                    </div>
                                </div>

                                <x-slot name="footer">
                                        <x-button type="button" @click="clearFilters()" variant="link-muted">
                                            Limpiar filtros
                                        </x-button>
                                    <x-button type="button" @click="applyFilters(); open = false" variant="primary">
                                        Aplicar Filtros
                                    </x-button>
                                </x-slot>
                            </x-filters-popover>
                        </div>

                        {{-- Active Chips Row --}}
                        @if($activeCount > 0)
                            <div class="flex flex-wrap items-center gap-2 px-4 pb-4 md:px-6 md:pb-4 pt-0">
                                @if($statusFilter)
                                    @php
                                        $statusNames = ['borrador' => 'Borrador', 'pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada'];
                                    @endphp
                                    <x-filter-chip label="Estado" :value="$statusNames[$statusFilter] ?? $statusFilter"
                                        wire:click="$set('statusFilter', '')" />
                                @endif
                                @if($projectFilter)
                                    <x-filter-chip label="Proyecto" :value="$projects[$projectFilter] ?? 'Desconocido'"
                                        wire:click="$set('projectFilter', '')" />
                                @endif
                                @if($creatorFilter)
                                    <x-filter-chip label="Creador" :value="$creators[$creatorFilter] ?? 'Desconocido'"
                                        wire:click="$set('creatorFilter', '')" />
                                @endif
                                @if($vendorFilter)
                                    <x-filter-chip label="Proveedor" :value="$vendors[$vendorFilter] ?? 'Desconocido'"
                                        wire:click="$set('vendorFilter', '')" />
                                @endif
                                @if($periodFilter)
                                    @php
                                        $periodNames = ['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año', 'custom' => 'Personalizado'];
                                        $periodLabel = $periodNames[$periodFilter] ?? $periodFilter;
                                        if ($periodFilter === 'custom' && ($dateFrom || $dateTo)) {
                                            $periodLabel .= ' (' . ($dateFrom ?: 'Inicio') . ' - ' . ($dateTo ?: 'Hoy') . ')';
                                        }
                                    @endphp
                                    <x-filter-chip label="Período" :value="$periodLabel"
                                        wire:click="$set('periodFilter', ''); $set('dateFrom', ''); $set('dateTo', '')" />
                                @endif

                                @if($activeCount > 1)
                                    <x-button wire:click="clearAllFilters" variant="link-danger-muted" icon="eraser" class="!text-xs !min-h-0 ml-auto">
                                        Limpiar todo
                                    </x-button>
                                @endif
                            </div>
                        @endif
                    </div> {{-- End Header Group --}}
                @endif

                <div class="relative">
                    <div class="w-full">
                        <x-card.table class="hidden md:block">
                            @if($requisitions->isEmpty() && !$hasActiveFilters)
                                <div wire:loading.class="hidden"
                                    wire:target="search, statusFilter, projectFilter, periodFilter, creatorFilter, vendorFilter, previousPage, nextPage, gotoPage"
                                    class="p-8">
                                    <x-empty-state icon="clipboard-list" title="No hay requisiciones registradas"
                                        message="Crea una requisición o sube una cotización para comenzar." />
                                </div>
                            @endif
                            <table
                                class="w-full table-fixed min-w-[1200px] {{ $requisitions->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}"
                                @if($requisitions->isEmpty()) wire:loading.class.remove="hidden"
                                    wire:target="search, statusFilter, projectFilter, periodFilter, creatorFilter, vendorFilter, previousPage, nextPage, gotoPage"
                                @endif>
                                <colgroup>
                                    <col class="w-14"> {{-- Checkbox --}}
                                    <col class="w-[12%]"> {{-- Folio --}}
                                    <col class="w-[22%]"> {{-- Proyecto --}}
                                    <col class="w-[10%]"> {{-- Fecha --}}
                                    <col class="w-[12%]"> {{-- Creador --}}
                                    <col class="w-[16%]"> {{-- Proveedor --}}
                                    <col class="w-[8%]"> {{-- Total --}}
                                    <col class="w-[8%]"> {{-- Estado --}}
                                    <col class="w-24"> {{-- Acciones --}}
                                </colgroup>
                                <thead class="bg-surface-main/50 border-b border-border">
                                    <tr>
                                        <th class="actions text-center pl-4 pr-2">
                                            <x-table-checkbox 
                                                x-bind:checked="allSelected"
                                                @change="toggleAll({{ json_encode($requisitions->pluck('id')->toArray()) }})"
                                            />
                                        </th>
                                        <x-sortable-header field="number" label="Folio" :sortField="$sortField"
                                            :sortDirection="$sortDirection" />
                                        <x-sortable-header field="project_id" label="Proyecto" :sortField="$sortField"
                                            :sortDirection="$sortDirection" />
                                        <x-sortable-header field="date" label="Fecha" :sortField="$sortField"
                                            :sortDirection="$sortDirection" />
                                        <th>Creador</th>
                                        <th>Proveedor</th>
                                        <x-sortable-header field="total" label="Total" :sortField="$sortField"
                                            :sortDirection="$sortDirection" align="right" class="numeric" />
                                        <x-sortable-header field="status" label="Estado" :sortField="$sortField"
                                            :sortDirection="$sortDirection" />
                                        <th class="actions text-right pr-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody wire:loading.class="hidden"
                                    wire:target="search, statusFilter, projectFilter, periodFilter, creatorFilter, vendorFilter, previousPage, nextPage, gotoPage">
                                    @if($requisitions->isEmpty() && $hasActiveFilters)
                                        <tr>
                                            <td colspan="9" class="p-8">
                                                <x-empty-state icon="search" title="No se encontraron requisiciones"
                                                    message="Intenta ajustar tus filtros de búsqueda." />
                                            </td>
                                        </tr>
                                    @else
                                        @foreach($requisitions as $req)
                                            <x-requisitions.table-row :req="$req" />
                                        @endforeach
                                    @endif
                                </tbody>
                                <tbody wire:loading.class.remove="hidden"
                                    wire:target="search, statusFilter, projectFilter, periodFilter, creatorFilter, vendorFilter, previousPage, nextPage, gotoPage"
                                    class="hidden">
                                    @for($i = 0; $i < 5; $i++)
                                        <tr class="opacity-{{ 100 - ($i * 15) }}">
                                            <td class="text-center">
                                                <x-skeleton class="w-4 h-4 rounded mx-auto" />
                                            </td>
                                            <td>
                                                <x-skeleton class="h-4 rounded w-16" />
                                            </td>
                                            <td>
                                                <x-skeleton class="h-4 rounded w-24" />
                                            </td>
                                            <td>
                                                <x-skeleton class="h-4 rounded w-20" />
                                            </td>
                                            <td>
                                                <x-skeleton class="h-4 rounded w-24" />
                                            </td>
                                            <td>
                                                <x-skeleton class="h-4 rounded w-32" />
                                            </td>
                                            <td class="text-right">
                                                <x-skeleton class="h-4 rounded w-20 ml-auto" />
                                            </td>
                                            <td>
                                                <x-skeleton class="h-6 rounded w-20" />
                                            </td>
                                            <td class="text-right">
                                                <div class="flex justify-end gap-1">
                                                    <x-skeleton class="w-8 h-8 rounded" />
                                                    <x-skeleton class="w-8 h-8 rounded" />
                                                </div>
                                            </td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </x-card.table>

                        {{-- Tarjetas Móviles (Mobile View) --}}
                        <div class="md:hidden flex flex-col gap-4 mt-2">
                            <div wire:loading.class="hidden"
                                wire:target="search, statusFilter, projectFilter, periodFilter, creatorFilter, vendorFilter, previousPage, nextPage, gotoPage"
                                class="flex flex-col gap-4">
                                @if($requisitions->isNotEmpty())
                                    @foreach($requisitions as $req)
                                        <x-requisitions.mobile-card :req="$req" />
                                    @endforeach
                                @elseif($hasActiveFilters)
                                    <div class="p-12">
                                        <x-empty-state icon="search" title="No se encontraron requisiciones"
                                            message="Intenta ajustar tus filtros de búsqueda." />
                                    </div>
                                @else
                                    <div class="p-12">
                                        <x-empty-state icon="clipboard-list" title="No hay requisiciones registradas"
                                            message="Crea una requisición o sube una cotización para comenzar." />
                                    </div>
                                @endif
                            </div>

                            {{-- Skeletons Móviles --}}
                            <div wire:loading.class.remove="hidden"
                                wire:target="search, statusFilter, projectFilter, periodFilter, creatorFilter, vendorFilter, previousPage, nextPage, gotoPage"
                                class="hidden flex flex-col gap-4 mt-2">
                                @for($i = 0; $i < 4; $i++)
                                    <div
                                        class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm opacity-{{ 100 - ($i * 15) }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <x-skeleton class="w-4 h-4 rounded-sm shrink-0" />
                                                <x-skeleton class="h-5 w-24 rounded" />
                                                <x-skeleton class="h-5 w-20 rounded-full" />
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <x-skeleton class="w-7 h-7 rounded-md" />
                                            </div>
                                        </div>
                                        <div class="pl-8 flex flex-col gap-3">
                                            <div class="flex gap-3">
                                                <x-skeleton class="h-3 w-28 rounded" />
                                                <x-skeleton class="h-3 w-20 rounded" />
                                            </div>
                                            <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                                                <div>
                                                    <x-skeleton class="h-2 w-12 mb-1.5 rounded" />
                                                    <x-skeleton class="h-4 w-24 rounded" />
                                                </div>
                                                <div>
                                                    <x-skeleton class="h-2 w-12 mb-1.5 rounded" />
                                                    <x-skeleton class="h-4 w-24 rounded" />
                                                </div>
                                                <div class="col-span-2">
                                                    <x-skeleton class="h-2 w-12 mb-1.5 rounded" />
                                                    <x-skeleton class="h-5 w-16 rounded" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>

                    {{-- Bulk Actions Bar --}}
                    <x-bulk-actions-bar>
                        @if(auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*'))
                            <div x-show="canApproveSelection" x-cloak class="flex items-center gap-2">
                                <x-button @click="$dispatch('confirm-action', {
                                            title: 'Aprobar Seleccionadas',
                                            description: 'Se aprobarán todas las requisiciones pendientes de tu selección.',
                                            confirmLabel: 'Aprobar seleccionadas',
                                            variant: 'success',
                                            action: 'approveSelected',
                                            params: []
                                        })" variant="success" icon="check-circle">
                                    Aprobar
                                </x-button>
                                <x-button wire:click="openBulkRejectModal" variant="warning" icon="x-octagon"
                                    target="openBulkRejectModal">
                                    Rechazar
                                </x-button>
                            </div>
                        @endif

                        <div class="h-8 w-px bg-border mx-1 hidden sm:block"></div>

                        {{-- Menú de Exportación --}}
                        <x-dropdown align="top" width="56">
                            <x-slot name="trigger">
                                <x-button variant="secondary" icon="file-down"
                                    wire:target="exportPdfZip, exportCsvSummary, exportCsvDetailed"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove
                                        wire:target="exportPdfZip, exportCsvSummary, exportCsvDetailed">Exportar</span>
                                    <span wire:loading
                                        wire:target="exportPdfZip, exportCsvSummary, exportCsvDetailed">Exportando...</span>
                                </x-button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link as="button" wire:click="exportCsvSummary" icon="table">
                                    Resumen (CSV)
                                </x-dropdown-link>
                                <x-dropdown-link as="button" wire:click="exportCsvDetailed" icon="list-checks">
                                    Detallado con Ítems (CSV)
                                </x-dropdown-link>
                                <div class="border-t border-border my-1"></div>
                                <x-dropdown-link as="button" wire:click="exportPdfZip" icon="file-archive">
                                    PDFs en ZIP
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>

                        <x-button @click="$dispatch('confirm-action', {
                            title: 'Eliminar Seleccionadas',
                            description: 'Se eliminarán permanentemente los borradores y rechazadas de tu selección.',
                            confirmLabel: 'Eliminar',
                            variant: 'danger',
                            action: 'deleteSelected',
                            params: []
                        })" variant="danger" icon="trash-2">
                            Eliminar
                        </x-button>
                    </x-bulk-actions-bar>
                </div>

                @if($requisitions->hasPages())
                    <x-card.footer>
                        {{ $requisitions->links() }}
                    </x-card.footer>
                @endif
            </div>
        </div>

        {{-- Reject Modal (RF-REQ-09: extraído a partial compartido) --}}
        @include('livewire.requisitions._reject-modal')

        {{-- Drawer de Detalle Rápido --}}
        <livewire:requisitions.requisition-detail-drawer />

        {{-- Diálogo de confirmación global --}}
        {{-- ═══════ PREVIEW MODAL ═══════ --}}
        <x-preview-modal />
    </div>
    <x-confirm-modal />
</div>