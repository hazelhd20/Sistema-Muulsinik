<div x-data="{
    activeTab: 'todas',
    showPreviewModal: false,
    previewUrl: null,
    previewType: null,
    showFilters: false,
    isPdf() {
        return this.previewType === 'application/pdf' || (this.previewUrl && this.previewUrl.toLowerCase().includes('.pdf'));
    },
    isImage() {
        return (this.previewType && this.previewType.startsWith('image/')) || (this.previewUrl && this.previewUrl.match(/\.(jpeg|jpg|gif|png)$/i));
    },
    openPreview(url, mimeType) {
        this.previewUrl = url;
        this.previewType = mimeType;
        this.showPreviewModal = true;
    },
    filterStatus: '',
    filterProject: '',
    filterCreator: '',
    filterVendor: '',
    filterPeriod: '',
    initFilters() {
        this.filterStatus = $wire.statusFilter || '';
        this.filterProject = $wire.projectFilter || '';
        this.filterCreator = $wire.creatorFilter || '';
        this.filterVendor = $wire.vendorFilter || '';
        this.filterPeriod = $wire.periodFilter || '';
    },
    applyFilters() {
        $wire.set('statusFilter', this.filterStatus, false);
        $wire.set('projectFilter', this.filterProject, false);
        $wire.set('creatorFilter', this.filterCreator, false);
        $wire.set('vendorFilter', this.filterVendor, false);
        $wire.set('periodFilter', this.filterPeriod, false);
        $wire.$refresh();
    },
    clearFilters() {
        this.filterStatus = '';
        this.filterProject = '';
        this.filterCreator = '';
        this.filterVendor = '';
        this.filterPeriod = '';
        this.applyFilters();
    },
    selectedRows: @entangle('selectedRows'),
    get allSelected() {
        return this.selectedRows.length > 0 && this.selectedRows.length === {{ $requisitions->count() }};
    },
    toggleAll() {
        if (this.allSelected) {
            this.selectedRows = [];
        } else {
            this.selectedRows = [{{ $requisitions->pluck('id')->join(',') }}].map(String);
        }
    },
    init() {
        this.initFilters();
    }
}">
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
    <div class="mb-6 border-b border-border">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button @click="activeTab = 'todas'"
                :class="activeTab === 'todas' ? 'border-primary-500 text-primary-600' : 'border-transparent text-text-muted hover:border-border hover:text-text-primary'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-small font-medium transition-colors">
                Requisiciones
            </button>
            <button @click="activeTab = 'borradores'"
                :class="activeTab === 'borradores' ? 'border-primary-500 text-primary-600' : 'border-transparent text-text-muted hover:border-border hover:text-text-primary'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-small font-medium inline-flex items-center gap-2 transition-colors">
                Borradores y Procesos
                @if($pendingQuotations->count() > 0)
                    <span class="bg-primary-100 text-primary-600 py-0.5 px-2 rounded-full text-xs-fluid font-bold">{{ $pendingQuotations->count() }}</span>
                @endif
            </button>
        </nav>
    </div>

    <div x-show="activeTab === 'todas'" x-cloak wire:key="tab-todas-filters">
    @php
        $activeCount = ($statusFilter ? 1 : 0) + ($projectFilter ? 1 : 0) + ($periodFilter ? 1 : 0) + ($creatorFilter ? 1 : 0) + ($vendorFilter ? 1 : 0);
    @endphp

    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center justify-between w-full">
        {{-- Search: compact width --}}
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar requisición..." />

        {{-- Filters Popover --}}
        <x-filters-popover :activeCount="$activeCount" :columns="2" @filters-opened="initFilters()">
            <x-form-field label="Estado">
                <x-custom-select x-model="filterStatus" :options="['borrador' => 'Borrador', 'pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada']" placeholder="Todos los estados" />
            </x-form-field>

            <x-form-field label="Proyecto">
                <x-custom-select x-model="filterProject" :options="$projects->pluck('name', 'id')->toArray()" placeholder="Todos los proyectos" />
            </x-form-field>

            <x-form-field label="Creador">
                <x-custom-select x-model="filterCreator" :options="$creators->pluck('name', 'id')->toArray()" placeholder="Todos los creadores" />
            </x-form-field>

            <x-form-field label="Proveedor">
                <x-custom-select x-model="filterVendor" :options="$vendors->pluck('trade_name', 'id')->toArray()" placeholder="Todos los proveedores" />
            </x-form-field>

            <x-form-field label="Período">
                <x-custom-select x-model="filterPeriod" :options="['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año']" placeholder="Todos los períodos" />
            </x-form-field>

            <x-slot name="footer">
                <button type="button" @click="clearFilters()" class="text-small text-text-muted hover:text-text-primary transition-colors font-medium">
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
    <div class="flex flex-wrap items-center gap-2 mb-4">
        @if($statusFilter)
            @php
                $statusNames = ['borrador' => 'Borrador', 'pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada'];
            @endphp
            <x-filter-chip label="Estado" :value="$statusNames[$statusFilter] ?? $statusFilter" wire:click="$set('statusFilter', '')" />
        @endif
        @if($projectFilter)
            <x-filter-chip label="Proyecto" :value="$projects->firstWhere('id', $projectFilter)?->name ?? 'Desconocido'" wire:click="$set('projectFilter', '')" />
        @endif
        @if($creatorFilter)
            <x-filter-chip label="Creador" :value="$creators->firstWhere('id', $creatorFilter)?->name ?? 'Desconocido'" wire:click="$set('creatorFilter', '')" />
        @endif
        @if($vendorFilter)
            <x-filter-chip label="Proveedor" :value="$vendors->firstWhere('id', $vendorFilter)?->trade_name ?? 'Desconocido'" wire:click="$set('vendorFilter', '')" />
        @endif
        @if($periodFilter)
            @php
                $periodNames = ['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año'];
            @endphp
            <x-filter-chip label="Período" :value="$periodNames[$periodFilter] ?? $periodFilter" wire:click="$set('periodFilter', '')" />
        @endif
    </div>
    @endif
    </div>

    <div x-show="activeTab === 'borradores'" x-cloak wire:key="tab-borradores">
        <livewire:requisitions.pending-quotations-list />
    </div>

    <div x-show="activeTab === 'todas'" x-cloak wire:key="tab-todas-table">
    {{-- Requisitions Table --}}
    <div class="relative min-h-[200px]">
        <div wire:loading.class="hidden"
            wire:target="search, statusFilter, projectFilter, periodFilter, previousPage, nextPage, gotoPage"
            class="w-full">
            <div class="table-container">
                @if($requisitions->isNotEmpty())
                    <table>
                        <thead class="bg-surface-main/50 border-b border-border">
                            <tr>
                                <th class="w-10 pl-4 pr-2 text-center">
                                    <x-table-checkbox
                                        x-bind:checked="allSelected"
                                        x-on:change="toggleAll()"
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
                                    :sortDirection="$sortDirection" align="right" />
                                <x-sortable-header field="status" label="Estado" :sortField="$sortField"
                                    :sortDirection="$sortDirection" class="w-1 whitespace-nowrap" />
                                <th class="w-1 whitespace-nowrap text-right pr-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requisitions as $req)
                                <tr wire:key="requisition-row-{{ $req->id }}"
                                    class="group hover:bg-gray-50/80 transition-colors duration-150"
                                    :class="selectedRows.includes('{{ $req->id }}') ? 'bg-primary-50/50' : ''">
                                    <td class="pl-4 pr-2 text-center" @click.stop>
                                        <x-table-checkbox x-model="selectedRows" value="{{ $req->id }}" />
                                    </td>
                                    <td class="font-medium whitespace-nowrap">
                                        {{ $req->number ?? 'REQ-' . str_pad($req->id, 5, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="max-w-[150px] truncate" title="{{ $req->project->name ?? '—' }}">
                                        {{ $req->project->name ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap">
                                        {{ $req->date?->format('d/m/Y') }}
                                    </td>
                                    <td class="max-w-[120px] truncate" title="{{ $req->creator->name ?? '—' }}">
                                        {{ $req->creator->name ?? '—' }}
                                    </td>
                                    <td class="max-w-[150px] truncate" title="{{ $req->vendor?->name ?? '—' }}">
                                        {{ $req->vendor?->name ?? '—' }}
                                    </td>
                                    <td class="text-right font-semibold tabular-nums text-text-primary">
                                        ${{ number_format($req->total, 2, '.', ',') }}
                                    </td>
                                    <td class="w-1 whitespace-nowrap py-3">
                                        <x-status-badge :status="$req->status" :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
                                    </td>
                                    <td class="w-1 whitespace-nowrap pr-4 py-3" @click.stop>
                                        <div class="flex items-center justify-end">
                                            <x-dropdown align="right" width="48">
                                                <x-slot name="trigger">
                                                    <x-button variant="icon" icon="more-vertical" class="text-text-muted hover:text-text-primary" aria-label="Opciones" title="Opciones" />
                                                </x-slot>

                                                <x-slot name="content">
                                                    <x-dropdown-link as="button" type="button" @click="$dispatch('open-requisition-detail', { id: {{ $req->id }} })" icon="eye">
                                                        Ver detalles
                                                    </x-dropdown-link>

                                                    @if($req->quotations->isNotEmpty())
                                                        @php
                                                            $firstQuot = $req->quotations->first();
                                                            $fileUrl = route('file.preview', ['path' => $firstQuot->file_path]);
                                                            $mime = str_ends_with(strtolower($firstQuot->file_path), '.pdf') ? 'application/pdf' : 'image/jpeg';
                                                        @endphp
                                                        <x-dropdown-link as="button" type="button" @click="openPreview('{{ $fileUrl }}', '{{ $mime }}')" icon="file-search">
                                                            Ver cotización
                                                        </x-dropdown-link>
                                                    @endif

                                                    <x-dropdown-link as="a" href="{{ route('requisiciones.pdf', $req->id) }}" target="_blank" icon="file-down">
                                                        Descargar PDF
                                                    </x-dropdown-link>

                                                    @if($req->status === 'borrador')
                                                        <div class="border-t border-border my-1"></div>
                                                        <x-dropdown-link as="button" type="button"
                                                            @click="$dispatch('confirm-action', {
                                                                title: 'Solicitar Aprobación',
                                                                description: 'La requisición será enviada a los aprobadores del sistema.',
                                                                confirmLabel: 'Enviar a aprobación',
                                                                variant: 'primary',
                                                                action: 'submitForApproval',
                                                                params: [{{ $req->id }}]
                                                            })"
                                                            icon="send">
                                                            Solicitar aprobación
                                                        </x-dropdown-link>
                                                    @endif

                                                    @if($req->status === 'pendiente' && auth()->user()->hasPermission('requisiciones.aprobar'))
                                                        <div class="border-t border-border my-1"></div>
                                                        <x-dropdown-link as="button" type="button"
                                                            @click="$dispatch('confirm-action', {
                                                                title: 'Aprobar Requisición',
                                                                description: 'Cambiará a estado Aprobada y se notificará al solicitante.',
                                                                confirmLabel: 'Aprobar',
                                                                variant: 'success',
                                                                action: 'approve',
                                                                params: [{{ $req->id }}]
                                                            })"
                                                            icon="check-circle" success="true">
                                                            Aprobar
                                                        </x-dropdown-link>
                                                        <x-dropdown-link as="button" wire:click="openRejectModal({{ $req->id }})" danger="true" icon="x-circle">
                                                            Rechazar
                                                        </x-dropdown-link>
                                                    @endif

                                                    @if(in_array($req->status, ['borrador', 'rechazada']))
                                                        <div class="border-t border-border my-1"></div>
                                                        <x-dropdown-link as="button" type="button"
                                                            @click="$dispatch('confirm-action', {
                                                                title: 'Eliminar Requisición',
                                                                description: 'Esta acción es permanente y no se puede deshacer.',
                                                                confirmLabel: 'Eliminar',
                                                                variant: 'danger',
                                                                action: 'deleteRequisition',
                                                                params: [{{ $req->id }}]
                                                            })"
                                                            danger="true" icon="trash-2">
                                                            Eliminar
                                                        </x-dropdown-link>
                                                    @endif
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <x-empty-state icon="clipboard-list" title="No hay requisiciones registradas"
                        message="Crea una requisición o sube una cotización para comenzar." />
                @endif
            </div>
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden"
            wire:target="search, statusFilter, projectFilter, periodFilter, previousPage, nextPage, gotoPage"
            class="hidden absolute inset-0 w-full z-10 bg-surface-main">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th class="w-10"></th>
                            <th>Folio</th>
                            <th>Proyecto</th>
                            <th>Fecha</th>
                            <th>Creador</th>
                            <th>Proveedor</th>
                            <th class="text-right">Total</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 5; $i++)
                            <tr>
                                <td class="text-center">
                                    <x-skeleton class="w-4 h-4  rounded mx-auto" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-16" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-24" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-20" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-24" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-32" />
                                </td>
                                <td class="text-right">
                                    <x-skeleton class="h-4  rounded w-20 ml-auto" />
                                </td>
                                <td>
                                    <x-skeleton class="h-6  rounded-full w-20" />
                                </td>
                                <td class="text-right flex justify-end gap-1">
                                    <x-skeleton class="w-8 h-8  rounded" />
                                    <x-skeleton class="w-8 h-8  rounded" />
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $requisitions->links() }}</div>
    </div>



    {{-- Reject Modal (RF-REQ-09: comentario obligatorio) --}}
    @if($showRejectModal)
        <x-modal show="showRejectModal"
            :title="$isBulkReject ? 'Rechazar Requisiciones Seleccionadas' : 'Rechazar Requisición'"
            :subtitle="$isBulkReject ? 'Indica el motivo del rechazo para todas las requisiciones seleccionadas (obligatorio)' : 'Indica el motivo del rechazo (obligatorio)'"
            maxWidth="md">
            <form wire:submit="confirmReject" class="p-5 space-y-4">
                <x-form-field label="Motivo del rechazo" required error="{{ $errors->first('rejectionComment') }}">
                    <textarea wire:model="rejectionComment" class="input" rows="3"
                        placeholder="Explica por qué esta(s) requisición(es) fue(ron) rechazada(s)..."></textarea>
                </x-form-field>
                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <x-button wire:click="$set('showRejectModal', false)" variant="secondary">Cancelar</x-button>
                    <x-button type="submit" variant="danger">
                        Confirmar Rechazo
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- Bulk Actions Bar --}}
    <x-bulk-actions-bar>
        @if(auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*'))
            <button type="button"
                @click="$dispatch('confirm-action', {
                    title: 'Aprobar Seleccionadas',
                    description: 'Se aprobarán todas las requisiciones pendientes de tu selección.',
                    confirmLabel: 'Aprobar seleccionadas',
                    variant: 'success',
                    action: 'approveSelected',
                    params: []
                })"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs-fluid font-semibold bg-success hover:bg-success-hover text-white transition-colors cursor-pointer shrink-0">
                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                Aprobar
            </button>
            <button type="button" wire:click="openBulkRejectModal" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-danger hover:bg-danger-hover text-white transition-colors cursor-pointer shrink-0">
                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                Rechazar
            </button>
        @endif

        <button type="button" wire:click="exportSelected" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 transition-colors cursor-pointer shrink-0">
            <i data-lucide="download" class="w-3.5 h-3.5"></i>
            Exportar
        </button>

        <button type="button"
            @click="$dispatch('confirm-action', {
                title: 'Eliminar Seleccionadas',
                description: 'Se eliminarán permanentemente los borradores y rechazadas de tu selección.',
                confirmLabel: 'Eliminar',
                variant: 'danger',
                action: 'deleteSelected',
                params: []
            })"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs-fluid font-semibold bg-danger hover:bg-danger-hover text-white transition-colors cursor-pointer shrink-0">
            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            Eliminar
        </button>
    </x-bulk-actions-bar>

    {{-- Drawer de Detalle Rápido --}}
    <livewire:requisitions.requisition-detail-drawer />

    {{-- Diálogo de confirmación global --}}
    <x-confirm-modal />

    {{-- ═══════ PREVIEW MODAL ═══════ --}}
    <x-preview-modal />
</div>
