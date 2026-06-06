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
    }
}">
    {{-- Header --}}
    <x-page-header subtitle="Compras" title="Requisiciones">
        <x-slot:actions>
            <a href="{{ route('requisiciones.manual') }}" class="btn-secondary" wire:navigate>
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nueva Manual
            </a>
            <a href="{{ route('requisiciones.upload') }}" class="btn-primary">
                <i data-lucide="scan-line" class="w-4 h-4"></i>
                Subir Cotización
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Tabs de Navegación --}}
    <div class="mb-6 border-b border-border">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button @click="activeTab = 'todas'"
                :class="activeTab === 'todas' ? 'border-primary-500 text-primary-600' : 'border-transparent text-text-muted hover:border-border hover:text-text-primary'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium flex items-center gap-2 transition-colors">
                <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                Requisiciones
            </button>
            <button @click="activeTab = 'borradores'"
                :class="activeTab === 'borradores' ? 'border-primary-500 text-primary-600' : 'border-transparent text-text-muted hover:border-border hover:text-text-primary'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium flex items-center gap-2 transition-colors">
                <i data-lucide="file-edit" class="w-4 h-4"></i>
                Borradores y Procesos
                @if($pendingQuotations->count() > 0)
                    <span class="bg-primary-100 text-primary-600 py-0.5 px-2 rounded-full text-xs-fluid font-bold ml-1">{{ $pendingQuotations->count() }}</span>
                @endif
            </button>
        </nav>
    </div>

    <div x-show="activeTab === 'todas'" x-cloak wire:key="tab-todas-filters">
    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center">
        {{-- Search: compact width --}}
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar requisición..." />

        {{-- Filters Toggle Button with counter badge --}}
        <button @click="showFilters = !showFilters" type="button" class="btn-secondary shrink-0"
            :class="{ 'bg-primary-50 border-primary-200 text-primary-700': showFilters || $wire.statusFilter || $wire.projectFilter || $wire.periodFilter }">
            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
            Filtros
            @php
                $activeCount = ($statusFilter ? 1 : 0) + ($projectFilter ? 1 : 0) + ($periodFilter ? 1 : 0);
            @endphp
            @if($activeCount > 0)
                <span
                    class="ml-1.5 px-1.5 py-0.5 bg-primary-600 text-white text-[10px] font-bold rounded-full">{{ $activeCount }}</span>
            @endif
        </button>

        <div class="flex-1"></div>

        {{-- Clear button: only when filters active --}}
        @if($search || $statusFilter || $projectFilter || $periodFilter)
            <button
                wire:click="$set('search', ''); $set('statusFilter', ''); $set('projectFilter', ''); $set('periodFilter', '');"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-small text-text-muted hover:text-text-primary transition-colors">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                Limpiar
            </button>
        @endif
    </div>

    {{-- Expandable Filters Panel --}}
    <div x-show="showFilters" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2" class="mb-6">
        <div class="card !p-4">
            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center flex-wrap">
                <div class="flex items-center gap-2 shrink-0">
                    <i data-lucide="filter" class="w-4 h-4 text-text-muted"></i>
                    <span class="text-small font-medium text-text-secondary">Filtrar por:</span>
                </div>
                <x-custom-select wire:model.live="statusFilter" :options="['borrador' => 'Borrador', 'pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada']" placeholder="Todos los estados"
                    class="w-full sm:w-40" />
                <x-custom-select wire:model.live="projectFilter" :options="$projects->pluck('name', 'id')->toArray()"
                    placeholder="Todos los proyectos" class="w-full sm:w-48" />
                <x-custom-select wire:model.live="periodFilter" :options="['this_month' => 'Este mes', 'last_month' => 'Mes anterior', 'this_quarter' => 'Este trimestre', 'this_year' => 'Este año']"
                    placeholder="Todos los períodos" class="w-full sm:w-44" />
            </div>
        </div>
    </div>
    </div>

    <div x-show="activeTab === 'borradores'" x-cloak wire:key="tab-borradores">
    {{-- Pending Quotations Area (Background Processing & Drafts) --}}
    @if($pendingQuotations->isNotEmpty())
        <div class="space-y-3" wire:poll.10s>
            @foreach($pendingQuotations as $pq)
                <div wire:key="pending-quotation-{{ $pq->id }}" class="flex items-center justify-between p-4 rounded-xl border bg-surface-card border-border hover:border-primary-300 transition-colors shadow-sm">
                    <div class="flex items-center gap-3">
                        @if($pq->isProcessing() || $pq->status === 'pending')
                            <div class="w-10 h-10 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 shadow-sm">
                                <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                            </div>
                            <div>
                                <p class="text-small font-semibold text-text-primary">Procesando cotización en segundo plano</p>
                                <p class="text-xs-fluid text-text-muted">{{ $pq->original_filename }} &bull; {{ $pq->created_at->diffForHumans() }}</p>
                            </div>
                        @elseif($pq->isCompleted())
                            <div class="w-10 h-10 rounded-full bg-success-light text-success flex items-center justify-center shrink-0 shadow-sm">
                                <i data-lucide="file-edit" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-small font-semibold text-text-primary">Borrador de Requisición listo</p>
                                <p class="text-xs-fluid text-text-muted">Procesado de: {{ $pq->original_filename }} &bull; {{ $pq->created_at->diffForHumans() }}</p>
                            </div>
                        @else
                            <div class="w-10 h-10 rounded-full bg-danger-light text-danger flex items-center justify-center shrink-0 shadow-sm">
                                <i data-lucide="file-x" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-small font-semibold text-text-primary">Error al extraer datos</p>
                                <p class="text-xs-fluid text-text-muted">{{ $pq->original_filename }} &bull; {{ $pq->created_at->diffForHumans() }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="dismissQuotation({{ $pq->id }})" wire:confirm="¿Descartar este borrador permanentemente?" class="btn-icon text-text-muted hover:text-danger hover:bg-danger-50 transition-colors" title="Descartar">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                        <a href="{{ route('requisiciones.upload', ['id' => $pq->id]) }}" wire:navigate class="btn-secondary text-small">
                            {{ $pq->isProcessing() || $pq->status === 'pending' ? 'Ver progreso' : 'Revisar y Continuar' }}
                            <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <x-empty-state icon="check-square" title="No hay borradores pendientes"
            message="Todas tus cotizaciones han sido procesadas o enviadas a aprobación." />
    @endif
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
                        <thead>
                            <tr>
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
                                    :sortDirection="$sortDirection" />
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requisitions as $req)
                                <tr wire:key="requisition-row-{{ $req->id }}" class="group cursor-pointer hover:bg-surface-hover"
                                    x-on:click="Livewire.navigate('{{ route('requisiciones.show', ['id' => $req->id]) }}')">
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
                                    <td>
                                        <x-status-badge :status="$req->status" :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
                                    </td>
                                    <td @click.stop>
                                        <div class="flex items-center justify-end gap-1">
                                            @if($req->quotations->isNotEmpty())
                                                @php
                                                    $firstQuot = $req->quotations->first();
                                                    $fileUrl = route('file.preview', ['path' => $firstQuot->file_path]);
                                                    $mime = str_ends_with(strtolower($firstQuot->file_path), '.pdf') ? 'application/pdf' : 'image/jpeg';
                                                @endphp
                                                <button type="button" @click="openPreview('{{ $fileUrl }}', '{{ $mime }}')"
                                                    class="btn-icon-primary" title="Ver cotización adjunta"
                                                    aria-label="Ver cotización adjunta">
                                                    <i data-lucide="file-search" class="w-4 h-4"></i>
                                                </button>
                                            @endif

                                            @if($req->status === 'borrador')
                                                <button wire:click="submitForApproval({{ $req->id }})"
                                                    wire:confirm="¿Enviar esta requisición a aprobación?" class="btn-icon-primary"
                                                    title="Enviar a aprobación" aria-label="Enviar a aprobación">
                                                    <i data-lucide="send" class="w-4 h-4"></i>
                                                </button>
                                            @endif

                                            @if($req->status === 'pendiente' && auth()->user()->hasPermission('requisiciones.aprobar'))
                                                <button wire:click="approve({{ $req->id }})"
                                                    wire:confirm="¿Aprobar esta requisición?"
                                                    class="btn-icon-success bg-success-light text-success hover:bg-success-light/80"
                                                    title="Aprobar" aria-label="Aprobar">
                                                    <i data-lucide="check" class="w-4 h-4"></i>
                                                </button>
                                            @endif

                                            {{-- Kebab Menu for secondary actions --}}
                                            <div x-data="{ openMenu: false }" class="relative">
                                                <button @click="openMenu = !openMenu" @click.away="openMenu = false"
                                                    class="btn-icon" aria-label="Más opciones" title="Más opciones">
                                                    <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                                </button>
                                                <div x-show="openMenu" x-transition
                                                    class="absolute right-0 top-full mt-1 w-44 bg-surface-card border border-border rounded-lg shadow-lg z-20 py-1"
                                                    style="display:none;">
                                                    <a href="{{ route('requisiciones.pdf', $req->id) }}" target="_blank"
                                                        class="flex items-center gap-2 px-3 py-2 text-small text-text-primary hover:bg-surface-hover w-full text-left">
                                                        <i data-lucide="file-down" class="w-4 h-4 text-text-muted"></i>
                                                        Descargar PDF
                                                    </a>
                                                    @if($req->status === 'pendiente' && auth()->user()->hasPermission('requisiciones.aprobar'))
                                                        <button wire:click="openRejectModal({{ $req->id }})"
                                                            class="flex items-center gap-2 px-3 py-2 text-small text-danger hover:bg-danger-light w-full text-left">
                                                            <i data-lucide="x" class="w-4 h-4"></i> Rechazar
                                                        </button>
                                                    @endif
                                                    @if(in_array($req->status, ['borrador', 'rechazada']))
                                                        <button wire:click="deleteRequisition({{ $req->id }})"
                                                            wire:confirm="¿Eliminar esta requisición?"
                                                            class="flex items-center gap-2 px-3 py-2 text-small text-danger hover:bg-danger-light w-full text-left">
                                                            <i data-lucide="trash-2" class="w-4 h-4"></i> Eliminar
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
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
                                    <div class="w-4 h-4 skeleton rounded mx-auto"></div>
                                </td>
                                <td>
                                    <div class="h-4 skeleton rounded w-16"></div>
                                </td>
                                <td>
                                    <div class="h-4 skeleton rounded w-24"></div>
                                </td>
                                <td>
                                    <div class="h-4 skeleton rounded w-20"></div>
                                </td>
                                <td>
                                    <div class="h-4 skeleton rounded w-24"></div>
                                </td>
                                <td>
                                    <div class="h-4 skeleton rounded w-32"></div>
                                </td>
                                <td class="text-right">
                                    <div class="h-4 skeleton rounded w-20 ml-auto"></div>
                                </td>
                                <td>
                                    <div class="h-6 skeleton rounded-full w-20"></div>
                                </td>
                                <td class="text-right flex justify-end gap-1">
                                    <div class="w-8 h-8 skeleton rounded"></div>
                                    <div class="w-8 h-8 skeleton rounded"></div>
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
        <x-modal show="showRejectModal" title="Rechazar Requisición" subtitle="Indica el motivo del rechazo (obligatorio)"
            maxWidth="md">
            <form wire:submit="confirmReject" class="p-5 space-y-4">
                <x-form-field label="Motivo del rechazo" required error="{{ $errors->first('rejectionComment') }}">
                    <textarea wire:model="rejectionComment" class="input" rows="3"
                        placeholder="Explica por qué esta requisición fue rechazada..."></textarea>
                </x-form-field>
                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <button type="button" wire:click="$set('showRejectModal', false)"
                        class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-danger">
                        Confirmar Rechazo
                    </button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- ═══════ PREVIEW MODAL ═══════ --}}
    <x-preview-modal />
</div>