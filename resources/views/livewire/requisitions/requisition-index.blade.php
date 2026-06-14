<div x-data="requisitionIndex(@entangle('selectedRows'), {{ $requisitions->mapWithKeys(fn($r) => [$r->id => $r->status])->toJson() }})"
    x-init="totalOnPage = {{ $requisitions->count() }}; init()">
    {{-- Header --}}
    <x-page-header subtitle="Compras" title="Requisiciones">
        <x-slot:actions>
            <x-button href="{{ route('requisiciones.manual') }}" variant="secondary" icon="plus" wire:navigate>
                Nueva Manual
            </x-button>
            <x-button href="{{ route('requisiciones.upload') }}" variant="primary" icon="scan-line">
                Subir Cotización
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Tabs de Navegación --}}
    <div class="tab-nav mb-6">
        <button @click="activeTab = 'todas'" :class="activeTab === 'todas' ? 'active' : ''" class="tab-btn">
            Requisiciones
        </button>
        <button @click="activeTab = 'borradores'" :class="activeTab === 'borradores' ? 'active' : ''" class="tab-btn">
            Borradores y Procesos
            @if($pendingQuotations->count() > 0)
                <span class="badge badge-primary ml-1">{{ $pendingQuotations->count() }}</span>
            @endif
        </button>
    </div>

    <div x-show="activeTab === 'borradores'" x-cloak wire:key="tab-borradores">
        <livewire:requisitions.pending-quotations-list />
    </div>

    <div x-show="activeTab === 'todas'" x-cloak wire:key="tab-todas-table">
        {{-- Unified Datagrid Card Container --}}
        <x-card class="mt-0">
            @php
                $activeCount = ($statusFilter ? 1 : 0) + ($projectFilter ? 1 : 0) + ($periodFilter ? 1 : 0) + ($creatorFilter ? 1 : 0) + ($vendorFilter ? 1 : 0);
                $hasActiveFilters = !empty($search) || $activeCount > 0;
            @endphp

            @if($requisitions->isNotEmpty() || $hasActiveFilters)
                {{-- Header Group (Search + Filters + Chips) --}}
                <div class="md:rounded-t-lg md:bg-surface-card">
                    {{-- Filters Bar --}}
                    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between w-full p-4 md:px-6 md:py-4">
                        {{-- Search: compact width --}}
                        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar requisición..." />

                        {{-- Filters Popover --}}
                        <x-filters-popover :activeCount="$activeCount" :columns="2" @filters-opened="initFilters()">
                            <x-form-field label="Estado">
                                <x-custom-select x-model="filterStatus" :options="['borrador' => 'Borrador', 'pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada']"
                                    placeholder="Todos los estados" />
                            </x-form-field>

                            <x-form-field label="Proyecto">
                                <x-custom-select x-model="filterProject" :options="$projects->pluck('name', 'id')->toArray()"
                                    placeholder="Todos los proyectos" />
                            </x-form-field>

                            <x-form-field label="Creador">
                                <x-custom-select x-model="filterCreator" :options="$creators->pluck('name', 'id')->toArray()"
                                    placeholder="Todos los creadores" />
                            </x-form-field>

                            <x-form-field label="Proveedor">
                                <x-custom-select x-model="filterVendor" :options="$vendors->pluck('trade_name', 'id')->toArray()"
                                    placeholder="Todos los proveedores" />
                            </x-form-field>

                            <x-form-field label="Período">
                                <x-custom-select x-model="filterPeriod" :options="['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año']"
                                    placeholder="Todos los períodos" />
                            </x-form-field>

                            <x-slot name="footer">
                                <button type="button" @click="clearFilters()"
                                    class="text-small text-text-muted hover:text-text-primary transition-colors font-medium">
                                    Limpiar todo
                                </button>
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
                                <x-filter-chip label="Proyecto" :value="$projects->firstWhere('id', $projectFilter)?->name ?? 'Desconocido'"
                                    wire:click="$set('projectFilter', '')" />
                            @endif
                            @if($creatorFilter)
                                <x-filter-chip label="Creador" :value="$creators->firstWhere('id', $creatorFilter)?->name ?? 'Desconocido'"
                                    wire:click="$set('creatorFilter', '')" />
                            @endif
                            @if($vendorFilter)
                                <x-filter-chip label="Proveedor" :value="$vendors->firstWhere('id', $vendorFilter)?->trade_name ?? 'Desconocido'" wire:click="$set('vendorFilter', '')" />
                            @endif
                            @if($periodFilter)
                                @php
                                    $periodNames = ['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año'];
                                @endphp
                                <x-filter-chip label="Período" :value="$periodNames[$periodFilter] ?? $periodFilter"
                                    wire:click="$set('periodFilter', '')" />
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
                    <table class="w-full table-fixed min-w-[1200px] {{ $requisitions->isEmpty() && !$hasActiveFilters ? 'hidden' : '' }}" @if($requisitions->isEmpty())
                        wire:loading.class.remove="hidden"
                        wire:target="search, statusFilter, projectFilter, periodFilter, creatorFilter, vendorFilter, previousPage, nextPage, gotoPage"
                    @endif>
                        <colgroup>
                            <col class="w-14">           {{-- Checkbox --}}
                            <col class="w-[12%]">        {{-- Folio --}}
                            <col class="w-[22%]">        {{-- Proyecto --}}
                            <col class="w-[10%]">        {{-- Fecha --}}
                            <col class="w-[12%]">        {{-- Creador --}}
                            <col class="w-[16%]">        {{-- Proveedor --}}
                            <col class="w-[8%]">         {{-- Total --}}
                            <col class="w-[8%]">         {{-- Estado --}}
                            <col class="w-24">           {{-- Acciones --}}
                        </colgroup>
                        <thead class="bg-surface-main/50 border-b border-border">
                            <tr>
                                <th class="actions text-center pl-4 pr-2">
                                    <input type="checkbox"
                                        class="w-4 h-4 rounded-sm text-primary-600 focus:ring-primary-500 border-border bg-surface-card cursor-pointer"
                                        x-bind:checked="allSelected"
                                        x-on:change="toggleAll([{{ $requisitions->pluck('id')->join(',') }}])" />
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
                                        <x-empty-state icon="search" title="No se encontraron requisiciones" message="Intenta ajustar tus filtros de búsqueda." />
                                    </td>
                                </tr>
                            @else
                                @foreach($requisitions as $req)
                                <tr wire:key="requisition-row-{{ $req->id }}"
                                    class="group hover:bg-surface-hover/80 transition-colors duration-150"
                                    :class="selectedRows.includes('{{ $req->id }}') ? 'bg-primary-50/50' : ''">
                                    <td class="actions text-center pl-4 pr-2" @click.stop>
                                        <x-table-checkbox x-model="selectedRows" value="{{ $req->id }}" />
                                    </td>
                                    <td class="font-semibold text-text-primary truncate max-w-0" title="{{ $req->number ?? 'REQ-' . str_pad($req->id, 5, '0', STR_PAD_LEFT) }}">
                                        {{ $req->number ?? 'REQ-' . str_pad($req->id, 5, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="truncate max-w-0 text-text-secondary" title="{{ $req->project->name ?? '—' }}">
                                        {{ $req->project->name ?? '—' }}
                                    </td>
                                    <td class="text-text-secondary">
                                        {{ $req->date?->format('d/m/Y') }}
                                    </td>
                                    <td class="truncate max-w-0 text-text-secondary" title="{{ $req->creator->name ?? '—' }}">
                                        {{ $req->creator->name ?? '—' }}
                                    </td>
                                    <td class="truncate max-w-0 text-text-secondary" title="{{ $req->vendor?->name ?? '—' }}">
                                        {{ $req->vendor?->name ?? '—' }}
                                    </td>
                                    <td class="text-right font-semibold tabular-nums text-text-primary numeric">
                                        ${{ number_format($req->total, 2, '.', ',') }}
                                    </td>
                                    <td class="py-3">
                                        <x-status-badge :status="$req->status" :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
                                        @if($req->status === 'rechazada' && $req->rejection_comment)
                                            <p class="text-[10px] text-danger mt-1 truncate max-w-0"
                                                title="{{ $req->rejection_comment }}">
                                                {{ $req->rejection_comment }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="actions pr-4 py-3" @click.stop>
                                        <div class="flex items-center justify-end">
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical"
                                                        class="text-text-muted hover:text-text-primary"
                                                        aria-label="Opciones" title="Opciones" />
                                                </x-slot>

                                                <x-slot name="content">
                                                    <x-dropdown-link as="button" type="button"
                                                        @click="$dispatch('open-requisition-detail', { id: {{ $req->id }} })"
                                                        icon="eye">
                                                        Ver detalles
                                                    </x-dropdown-link>

                                                    @if($req->quotations->isNotEmpty())
                                                        @php
                                                            $firstQuot = $req->quotations->first();
                                                            $fileUrl = route('file.preview', ['path' => $firstQuot->file_path]);
                                                            $mime = str_ends_with(strtolower($firstQuot->file_path), '.pdf') ? 'application/pdf' : 'image/jpeg';
                                                        @endphp
                                                        <x-dropdown-link as="button" type="button"
                                                            @click="openPreview('{{ $fileUrl }}', '{{ $mime }}')"
                                                            icon="file-search">
                                                            Ver cotización
                                                        </x-dropdown-link>
                                                    @endif

                                                    <x-dropdown-link as="a"
                                                        href="{{ route('requisiciones.pdf', $req->id) }}" target="_blank"
                                                        icon="file-down">
                                                        Descargar PDF
                                                    </x-dropdown-link>

                                                    @if($req->status === 'borrador' && $req->created_by === auth()->id())
                                                        <div class="border-t border-border my-1"></div>
                                                        @if(auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*'))
                                                            <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', {
                                                                                title: 'Aprobar Requisición',
                                                                                description: 'Al tener permisos de aprobación, la requisición se aprobará automáticamente.',
                                                                                confirmLabel: 'Aprobar',
                                                                                variant: 'success',
                                                                                action: 'submitForApproval',
                                                                                params: [{{ $req->id }}]
                                                                            })" icon="check-circle" success="true">
                                                                Aprobar
                                                            </x-dropdown-link>
                                                        @else
                                                            <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', {
                                                                                title: 'Solicitar Aprobación',
                                                                                description: 'La requisición será enviada a los aprobadores del sistema.',
                                                                                confirmLabel: 'Enviar a aprobación',
                                                                                variant: 'primary',
                                                                                action: 'submitForApproval',
                                                                                params: [{{ $req->id }}]
                                                                            })" icon="send">
                                                                Solicitar aprobación
                                                            </x-dropdown-link>
                                                        @endif
                                                    @endif

                                                    @if($req->status === 'pendiente' && auth()->user()->hasPermission('requisiciones.aprobar'))
                                                        <div class="border-t border-border my-1"></div>
                                                        <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', {
                                                                        title: 'Aprobar Requisición',
                                                                        description: 'Cambiará a estado Aprobada y se notificará al solicitante.',
                                                                        confirmLabel: 'Aprobar',
                                                                        variant: 'success',
                                                                        action: 'approve',
                                                                        params: [{{ $req->id }}]
                                                                    })" icon="check-circle" success="true">
                                                            Aprobar
                                                        </x-dropdown-link>
                                                        <x-dropdown-link as="button"
                                                            wire:click="openRejectModal({{ $req->id }})" danger="true"
                                                            icon="x-circle">
                                                            Rechazar
                                                        </x-dropdown-link>
                                                    @endif

                                                    @if(in_array($req->status, ['borrador', 'rechazada']))
                                                        <div class="border-t border-border my-1"></div>
                                                        <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', {
                                                                        title: 'Eliminar Requisición',
                                                                        description: 'Esta acción es permanente y no se puede deshacer.',
                                                                        confirmLabel: 'Eliminar',
                                                                        variant: 'danger',
                                                                        action: 'deleteRequisition',
                                                                        params: [{{ $req->id }}]
                                                                    })" danger="true" icon="trash-2">
                                                            Eliminar
                                                        </x-dropdown-link>
                                                    @endif
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </td>
                                </tr>
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
                <div class="md:hidden flex flex-col gap-4 p-4">
                    <div wire:loading.class="hidden"
                        wire:target="search, statusFilter, projectFilter, periodFilter, creatorFilter, vendorFilter, previousPage, nextPage, gotoPage"
                        class="flex flex-col gap-4">
                        @if($requisitions->isNotEmpty())
                            @foreach($requisitions as $req)
                                <div class="card p-4 flex flex-col gap-3 relative overflow-hidden transition-colors"
                                    :class="selectedRows.includes('{{ $req->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
                                    wire:key="req-mobile-card-{{ $req->id }}">

                                    <div class="flex justify-between items-start gap-2">
                                        <div class="flex items-start gap-3">
                                            <div class="pt-0.5">
                                                <x-table-checkbox x-model="selectedRows" value="{{ $req->id }}" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span
                                                        class="font-bold text-text-primary text-body">{{ $req->number ?? 'REQ-' . str_pad($req->id, 5, '0', STR_PAD_LEFT) }}</span>
                                                    <x-status-badge :status="$req->status" :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
                                                </div>
                                                <p class="text-xs text-text-secondary mt-1 truncate">
                                                    {{ $req->project->name ?? 'Sin proyecto' }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span
                                                class="font-bold text-text-primary tabular-nums text-body">${{ number_format($req->total, 2, '.', ',') }}</span>
                                        </div>
                                    </div>

                                    <div
                                        class="grid grid-cols-2 gap-2 text-xs text-text-muted bg-surface-main p-3 rounded-xl border border-border/50">
                                        <div class="flex items-center gap-1.5">
                                            <x-lucide-calendar class="w-3.5 h-3.5 shrink-0" />
                                            <span>{{ $req->date?->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 truncate">
                                            <x-lucide-user class="w-3.5 h-3.5 shrink-0" />
                                            <span class="truncate">{{ $req->creator->name ?? '—' }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 truncate col-span-2 mt-0.5">
                                            <x-lucide-building-2 class="w-3.5 h-3.5 shrink-0" />
                                            <span class="truncate">{{ $req->vendor?->name ?? 'Sin proveedor' }}</span>
                                        </div>
                                    </div>

                                    @if($req->status === 'rechazada' && $req->rejection_comment)
                                        <div
                                            class="bg-danger-50 text-danger-700 text-xs p-2.5 rounded-lg border border-danger-200 mt-1 flex items-start gap-2">
                                            <x-lucide-alert-circle class="w-4 h-4 shrink-0 mt-0.5" />
                                            <p class="leading-relaxed">{{ $req->rejection_comment }}</p>
                                        </div>
                                    @endif

                                    <div class="flex justify-end pt-3 border-t border-border/50 mt-1">
                                        <x-button @click="$dispatch('open-requisition-detail', { id: {{ $req->id }} })"
                                            variant="secondary" icon="eye" class="text-xs py-1.5 px-3">
                                            Ver Detalles
                                        </x-button>
                                    </div>
                                </div>
                            @endforeach
                        @elseif($hasActiveFilters)
                            <div class="p-12">
                                <x-empty-state icon="search" title="No se encontraron requisiciones" message="Intenta ajustar tus filtros de búsqueda." />
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
                        class="hidden flex flex-col gap-4">
                        @for($i = 0; $i < 4; $i++)
                            <div class="card p-4 flex flex-col gap-3 relative transition-colors opacity-{{ 100 - ($i * 15) }}">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="flex items-start gap-3">
                                        <x-skeleton class="w-4 h-4 rounded mt-0.5" />
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <x-skeleton class="h-5 w-20 rounded" />
                                                <x-skeleton class="h-5 w-16 rounded-full" />
                                            </div>
                                            <x-skeleton class="h-3 w-32 rounded mt-2" />
                                        </div>
                                    </div>
                                    <x-skeleton class="h-5 w-16 rounded" />
                                </div>
                                <div
                                    class="grid grid-cols-2 gap-2 bg-surface-hover/50 p-3 rounded-xl border border-border/50">
                                    <x-skeleton class="h-3 w-24 rounded" />
                                    <x-skeleton class="h-3 w-28 rounded" />
                                    <x-skeleton class="h-3 w-40 rounded col-span-2" />
                                </div>
                                <div class="flex justify-end pt-3 border-t border-border/50 mt-1">
                                    <x-skeleton class="h-8 w-24 rounded" />
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
                            <x-button wire:click="openBulkRejectModal" variant="warning" icon="x-octagon" target="openBulkRejectModal">
                                Rechazar
                            </x-button>
                        </div>
                    @endif

                    <div class="h-8 w-px bg-border mx-1 hidden sm:block"></div>

                    {{-- Menú de Exportación --}}
                    <x-dropdown align="top" width="56">
                        <x-slot name="trigger">
                            <x-button variant="secondary" icon="file-down"
                                wire:target="exportPdfZip, exportCsvSummary, exportCsvDetailed" wire:loading.attr="disabled">
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
        </x-card>
    </div>



    {{-- Reject Modal (RF-REQ-09: extraído a partial compartido) --}}
    @include('livewire.requisitions._reject-modal')

    {{-- Drawer de Detalle Rápido --}}
    <livewire:requisitions.requisition-detail-drawer />

    {{-- Diálogo de confirmación global --}}
    <x-confirm-modal />

    {{-- ═══════ PREVIEW MODAL ═══════ --}}
    <x-preview-modal />
</div>