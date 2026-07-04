<div x-data="{ tab: @entangle('tab').live }" x-on:livewire:navigated.window="tab = $wire.tab">
    <div>
        {{-- Header --}}
        <x-page-header subtitle="Compras" title="Requisiciones" :sticky="true">
            <x-slot:actions>
                <x-button href="{{ route('requisiciones.manual', ['source' => $tab]) }}" variant="secondary" icon="plus"
                    class="flex-1 sm:flex-initial justify-center" wire:navigate>
                    Nueva Manual
                </x-button>
                <x-button href="{{ route('requisiciones.upload', ['source' => $tab]) }}" variant="primary"
                    icon="scan-line" class="flex-1 sm:flex-initial justify-center" wire:navigate>
                    Subir Cotización
                </x-button>
            </x-slot:actions>
        </x-page-header>

        {{-- Tabs de Navegación --}}
        <div class="tab-nav mb-3 sm:mb-6">
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
            x-init="totalOnPageStatic = {{ $requisitions->count() }}; init()"
            data-total-on-page="{{ $requisitions->count() }}">
            {{-- Unified Datagrid Card Container --}}
            <div class="mt-0 flex flex-col bg-transparent md:bg-surface-card md:border md:border-border md:rounded-xl">
                @php
                    $activeCount = ($statusFilter ? 1 : 0) + ($projectFilter ? 1 : 0) + ($periodFilter ? 1 : 0) + ($creatorFilter ? 1 : 0) + ($vendorFilter ? 1 : 0);
                    $hasActiveFilters = !empty($search) || $activeCount > 0;
                @endphp

                @if($requisitions->isNotEmpty() || $hasActiveFilters)
                    {{-- Header Group (Search + Filters + Chips) --}}
                    <div
                        class="bg-transparent border-0 shadow-none md:card md:rounded-t-xl md:bg-surface-card md:border-0 md:shadow-none mb-4 md:mb-0">
                        {{-- Filters Bar --}}
                        <div class="flex flex-row gap-2.5 items-center justify-between w-full py-1 md:px-6 md:py-4">
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
                            <div class="flex flex-wrap items-center gap-2 pb-3 md:px-6 md:pb-4 pt-1">
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
                                    <x-button wire:click="clearAllFilters" variant="link-danger-muted" icon="eraser"
                                        class="!min-h-0 ml-auto">
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
                                    <col class="w-28"> {{-- Acciones --}}
                                </colgroup>
                                <thead class="bg-surface-main border-b border-border/40">
                                    <tr>
                                        <th class="actions pl-6 pr-2 text-left">
                                            <x-table-checkbox x-bind:checked="allSelected"
                                                @change="toggleAll({{ json_encode($requisitions->pluck('id')->toArray()) }})" />
                                        </th>
                                        <x-sortable-header field="number" label="Folio" :sortField="$sortField"
                                            :sortDirection="$sortDirection" />
                                        <x-sortable-header field="project_id" label="Proyecto" :sortField="$sortField"
                                            :sortDirection="$sortDirection" />
                                        <x-sortable-header field="date" label="Fecha" :sortField="$sortField"
                                            :sortDirection="$sortDirection" />
                                        <th class="text-xs-fluid font-semibold uppercase tracking-wider text-text-muted">Creador</th>
                                        <th class="text-xs-fluid font-semibold uppercase tracking-wider text-text-muted">Proveedor</th>
                                        <x-sortable-header field="total" label="Total" :sortField="$sortField"
                                            :sortDirection="$sortDirection" align="right" class="numeric" />
                                        <x-sortable-header field="status" label="Estado" :sortField="$sortField"
                                            :sortDirection="$sortDirection" />
                                        <th class="actions pr-6 text-right">Acciones</th>
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
                                            <td class="actions pl-6 pr-2 text-left">
                                                <x-skeleton class="w-4 h-4 rounded-sm" />
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
                                            <td class="actions pr-6 py-3">
                                                <div class="flex items-center justify-end">
                                                    <x-skeleton class="w-8 h-8 rounded-md" />
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
                                    <x-card
                                        class="p-4 flex flex-col gap-3 relative transition-colors shadow-sm opacity-{{ 100 - ($i * 15) }}">
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
                                    </x-card>
                                @endfor
                            </div>
                        </div>
                    </div>

                    {{-- Bulk Actions Bar --}}
                    <x-bulk-actions-bar>
                        {{-- Vista Escritorio: Todos los botones visibles --}}
                        <div class="hidden sm:flex items-center gap-2">
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
                                    <x-button wire:click="openBulkRejectModal" variant="secondary" icon="x-circle"
                                        target="openBulkRejectModal">
                                        Rechazar
                                    </x-button>
                                </div>
                            @endif

                            <div class="h-8 w-px bg-border/40 mx-1"></div>

                            {{-- Menú de Exportación (Inline CSS positioning) --}}
                            <div class="relative inline-flex" x-data="{ open: false }" @click.outside="open = false">
                                <x-button variant="secondary" icon="file-down" @click="open = !open"
                                    wire:target="exportPdfZip, exportCsvSummary, exportCsvDetailed"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove
                                        wire:target="exportPdfZip, exportCsvSummary, exportCsvDetailed">Exportar</span>
                                    <span wire:loading
                                        wire:target="exportPdfZip, exportCsvSummary, exportCsvDetailed">Exportando...</span>
                                </x-button>

                                <div x-show="open" x-transition:enter="transition-premium"
                                    x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave="transition-premium"
                                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                                    class="absolute z-50 bottom-full left-0 mb-2 w-56 rounded-xl border border-border bg-surface-card shadow-xl overflow-hidden"
                                    style="display: none;" @click="open = false">
                                    <x-dropdown-link as="button" wire:click="exportCsvSummary" icon="table">
                                        Resumen (CSV)
                                    </x-dropdown-link>
                                    <x-dropdown-link as="button" wire:click="exportCsvDetailed" icon="list-checks">
                                        Detallado con Ítems (CSV)
                                    </x-dropdown-link>
                                    <x-dropdown-link as="button" wire:click="exportPdfZip" icon="file-archive">
                                        PDFs en ZIP
                                    </x-dropdown-link>
                                </div>
                            </div>

                            @if(auth()->user()->hasPermission('requisiciones.editar') || auth()->user()->hasPermission('*'))
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
                            @endif
                        </div>

                        {{-- Vista Móvil: 1 Botón Principal Contextual + Menú de 3 Puntos --}}
                        <div class="flex sm:hidden items-center gap-1.5">
                            @if(auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*'))
                                <div x-show="canApproveSelection" x-cloak>
                                    <x-button size="sm" @click="$dispatch('confirm-action', {
                                                        title: 'Aprobar Seleccionadas',
                                                        description: 'Se aprobarán todas las requisiciones pendientes de tu selección.',
                                                        confirmLabel: 'Aprobar',
                                                        variant: 'success',
                                                        action: 'approveSelected',
                                                        params: []
                                                    })" variant="success" icon="check-circle">
                                        Aprobar
                                    </x-button>
                                </div>
                                <div x-show="!canApproveSelection" x-cloak>
                                    <x-button size="sm" wire:click="exportPdfZip" variant="secondary" icon="file-archive"
                                        wire:loading.attr="disabled" wire:target="exportPdfZip">
                                        <span wire:loading.remove wire:target="exportPdfZip">Exportar ZIP</span>
                                        <span wire:loading wire:target="exportPdfZip">ZIP...</span>
                                    </x-button>
                                </div>
                            @else
                                <div>
                                    <x-button size="sm" wire:click="exportPdfZip" variant="secondary" icon="file-archive"
                                        wire:loading.attr="disabled" wire:target="exportPdfZip">
                                        <span wire:loading.remove wire:target="exportPdfZip">Exportar ZIP</span>
                                        <span wire:loading wire:target="exportPdfZip">ZIP...</span>
                                    </x-button>
                                </div>
                            @endif

                            <div class="relative inline-flex" x-data="{ open: false }" @click.outside="open = false">
                                <x-button size="sm" variant="secondary" icon="more-vertical" aria-label="Más opciones"
                                    title="Más opciones" @click="open = !open" />

                                <div x-show="open" x-transition:enter="transition-premium"
                                    x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave="transition-premium"
                                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                                    class="absolute z-50 bottom-full right-0 mb-2 w-56 rounded-xl border border-border bg-surface-card shadow-xl overflow-hidden"
                                    style="display: none;" @click="open = false">
                                    @if(auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*'))
                                        <div x-show="canApproveSelection">
                                            <x-dropdown-link as="button" wire:click="openBulkRejectModal" icon="x-circle">
                                                Rechazar
                                            </x-dropdown-link>
                                        </div>
                                    @endif
                                    <x-dropdown-link as="button" wire:click="exportCsvSummary" icon="table">
                                        Exportar Resumen (CSV)
                                    </x-dropdown-link>
                                    <x-dropdown-link as="button" wire:click="exportCsvDetailed" icon="list-checks">
                                        Exportar Detallado (CSV)
                                    </x-dropdown-link>
                                    <x-dropdown-link as="button" wire:click="exportPdfZip" icon="file-archive">
                                        Exportar PDFs en ZIP
                                    </x-dropdown-link>
                                    @if(auth()->user()->hasPermission('requisiciones.editar') || auth()->user()->hasPermission('*'))
                                        <x-dropdown-link as="button" @click="$dispatch('confirm-action', {
                                                title: 'Eliminar Seleccionadas',
                                                description: 'Se eliminarán permanentemente los borradores y rechazadas de tu selección.',
                                                confirmLabel: 'Eliminar',
                                                variant: 'danger',
                                                action: 'deleteSelected',
                                                params: []
                                            })" danger="true" icon="trash-2">
                                            Eliminar
                                        </x-dropdown-link>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </x-bulk-actions-bar>
                </div>

                @if($requisitions->hasPages() || $requisitions->total() > 0)
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