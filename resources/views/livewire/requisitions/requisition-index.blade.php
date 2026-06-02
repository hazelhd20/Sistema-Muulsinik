<div x-data="{
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

    {{-- Filters Bar --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4 items-start sm:items-center">
        {{-- Search: compact width --}}
        <div class="relative w-full sm:w-72" x-data="{ focused: false }">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.50ms="search" type="search" placeholder="Buscar requisición..."
                class="input pl-10 pr-10 w-full" @focus="focused = true" @blur="focused = false">
            <button x-show="$wire.search" x-transition @click="$wire.search = ''" type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded hover:bg-surface-hover text-text-muted">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>

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
        <div class="card !bg-surface-hover/50 !p-4">
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

    {{-- Requisitions list --}}
    <div class="space-y-3">
        @forelse($requisitions as $req)
            @php
                $iconName = match ($req->status) { 'borrador' => 'file-edit', 'pendiente' => 'clock', 'aprobada' => 'check-circle', 'rechazada' => 'x-circle', default => 'file'};
                $iconBg = match ($req->status) { 'borrador' => 'bg-surface-hover', 'pendiente' => 'bg-amber-50', 'aprobada' => 'bg-emerald-50', 'rechazada' => 'bg-red-50', default => 'bg-surface-hover'};
                $iconColor = match ($req->status) { 'borrador' => 'text-text-muted', 'pendiente' => 'text-amber-600', 'aprobada' => 'text-emerald-600', 'rechazada' => 'text-danger', default => 'text-text-muted'};
            @endphp
            <div x-data="{ open: false }" class="card">

                {{-- ── Cuerpo principal ── --}}
                <div class="flex items-start gap-4">

                    {{-- Ícono de estado --}}
                    <div class="w-9 h-9 rounded-xl {{ $iconBg }} flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="{{ $iconName }}" class="w-4 h-4 {{ $iconColor }}"></i>
                    </div>

                    {{-- Info central --}}
                    <div class="flex-1 min-w-0">
                        {{-- Fila 1: número + badge --}}
                        <div class="flex items-center gap-2 mb-1.5">
                            <h3 class="text-small font-semibold text-text-primary">{{ $req->number ?? 'REQ-' . $req->id }}
                            </h3>
                            <x-status-badge :status="$req->status" :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" class="shrink-0" />
                        </div>

                        {{-- Fila 2: metadatos primarios --}}
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs-fluid text-text-muted">
                            <span class="flex items-center gap-1">
                                <i data-lucide="hard-hat" class="w-3 h-3 shrink-0"></i>
                                <span class="truncate max-w-[160px]">{{ $req->project->name ?? '—' }}</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <i data-lucide="calendar" class="w-3 h-3 shrink-0"></i>
                                {{ $req->date?->format('d/m/Y') }}
                            </span>
                            <span class="flex items-center gap-1">
                                <i data-lucide="user" class="w-3 h-3 shrink-0"></i>
                                {{ $req->creator->name ?? '—' }}
                            </span>
                            @if($req->vendor)
                                <span class="flex items-center gap-1">
                                    <i data-lucide="contact" class="w-3 h-3 shrink-0"></i>
                                    {{ $req->vendor->name }}
                                    @if($req->vendor->supplier)
                                        <span class="text-text-muted/60">· {{ $req->vendor->supplier->trade_name }}</span>
                                    @endif
                                </span>
                            @endif
                        </div>

                        {{-- Fila 3: anotaciones (solo si existen) --}}
                        @if($req->annotations)
                            <p class="mt-1.5 text-xs-fluid text-text-muted italic truncate max-w-lg"
                                title="{{ $req->annotations }}">
                                "{{ $req->annotations }}"
                            </p>
                        @endif
                    </div>

                    {{-- Total --}}
                    <div class="text-right shrink-0">
                        <p class="text-h2 text-text-primary leading-tight">${{ number_format($req->total, 2, '.', ',') }}
                        </p>
                        <p class="text-xs-fluid text-text-muted">estimado</p>
                    </div>
                </div>

                {{-- ── Footer: trigger collapsible + acciones ── --}}
                <div class="mt-3 pt-3 border-t border-border flex items-center justify-between">

                    {{-- Trigger de productos --}}
                    @if($req->items->isNotEmpty())
                        <button @click="open = !open"
                            class="flex items-center gap-1.5 text-xs-fluid font-medium text-text-muted hover:text-primary-600 transition-colors">
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200"
                                :class="open && 'rotate-180'"></i>
                            <span>{{ $req->items->count() }} {{ $req->items->count() === 1 ? 'producto' : 'productos' }}</span>
                        </button>
                    @else
                        <span class="text-text-muted">—</span>
                    @endif

                    {{-- Acciones --}}
                    <div class="flex items-center gap-1">
                        @if($req->quotations->isNotEmpty())
                            @php
                                $firstQuot = $req->quotations->first();
                                $fileUrl = route('file.preview', ['path' => $firstQuot->file_path]);
                                $mime = str_ends_with(strtolower($firstQuot->file_path), '.pdf') ? 'application/pdf' : 'image/jpeg';
                            @endphp
                            <button type="button" @click="openPreview('{{ $fileUrl }}', '{{ $mime }}')" class="btn-icon-primary"
                                title="Ver cotización adjunta">
                                <i data-lucide="file-search" class="w-4 h-4"></i>
                            </button>
                        @endif

                        @if($req->status === 'borrador')
                            <button wire:click="submitForApproval({{ $req->id }})"
                                wire:confirm="¿Enviar esta requisición a aprobación?" class="btn-icon-primary"
                                title="Enviar a aprobación">
                                <i data-lucide="send" class="w-4 h-4"></i>
                            </button>
                        @endif

                        @if($req->status === 'pendiente' && auth()->user()->hasPermission('requisiciones.aprobar'))
                            <button wire:click="approve({{ $req->id }})" wire:confirm="¿Aprobar esta requisición?"
                                class="btn-icon-primary" title="Aprobar">
                                <i data-lucide="check" class="w-4 h-4"></i>
                            </button>
                            <button wire:click="openRejectModal({{ $req->id }})" class="btn-icon-danger" title="Rechazar">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        @endif

                        <a href="{{ route('requisiciones.pdf', $req->id) }}" target="_blank" class="btn-icon-primary"
                            title="Descargar PDF">
                            <i data-lucide="file-down" class="w-4 h-4"></i>
                        </a>

                        @if(in_array($req->status, ['borrador', 'rechazada']))
                            <button wire:click="deleteRequisition({{ $req->id }})" wire:confirm="¿Eliminar esta requisición?"
                                class="btn-icon-danger" title="Eliminar">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- ── Tabla colapsable de productos ── --}}
                @if($req->items->isNotEmpty())
                    <div x-show="open" x-collapse x-cloak class="mt-3" style="display: none;">
                        <div class="table-embedded">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-center">Unidad</th>
                                        <th class="text-right">P. Unit.</th>
                                        <th class="text-right">Subtotal</th>
                                        <th class="text-right">IVA</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($req->items as $item)
                                        <tr>
                                            <td class="font-medium text-text-primary max-w-[180px] sm:max-w-[240px] truncate"
                                                title="{{ $item->product_name ?? $item->product?->canonical_name ?? '' }}">
                                                {{ $item->product_name ?? $item->product?->canonical_name ?? '—' }}</td>
                                            <td class="text-center text-text-secondary tabular-nums">
                                                {{ rtrim(rtrim(number_format($item->quantity, 4), '0'), '.') }}</td>
                                            <td class="text-center text-text-muted">
                                                {{ $item->measure?->abbreviation ?? $item->unit ?? '—' }}</td>
                                            <td class="text-right text-text-secondary tabular-nums">
                                                ${{ number_format($item->unit_price, 2, '.', ',') }}</td>
                                            <td class="text-right text-text-secondary tabular-nums">
                                                ${{ number_format($item->line_subtotal_computed, 2, '.', ',') }}</td>
                                            <td class="text-right tabular-nums">
                                                @if($item->tax_amount !== null)
                                                    <span
                                                        class="text-text-muted">${{ number_format($item->tax_amount, 2, '.', ',') }}</span>
                                                @else
                                                    <span class="text-text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-right font-semibold text-text-primary tabular-nums">
                                                ${{ number_format($item->line_total_computed, 2, '.', ',') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Totales --}}
                        @php
                            $reqSubtotal = $req->items->sum(fn($i) => $i->line_subtotal_computed);
                            $reqTax = $req->items->sum(fn($i) => (float) ($i->tax_amount ?? 0));
                            $reqTotal = $req->total;
                        @endphp
                        <div class="flex justify-end mt-3">
                            <div class="min-w-[260px] space-y-1.5">
                                <div class="flex items-center justify-between gap-6">
                                    <span class="text-small text-text-muted">Subtotal s/IVA</span>
                                    <span
                                        class="text-small font-medium text-text-secondary tabular-nums">${{ number_format($reqSubtotal, 2, '.', ',') }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-6">
                                    <span class="text-small text-text-muted">IVA (16%)</span>
                                    <span class="text-small font-medium text-text-muted tabular-nums">
                                        @if($reqTax > 0) ${{ number_format($reqTax, 2, '.', ',') }}
                                        @else <span class="text-text-muted">—</span> @endif
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-6 pt-1.5 border-t border-border">
                                    <span class="text-small font-semibold text-text-primary">Total</span>
                                    <span
                                        class="text-body font-bold text-text-primary tabular-nums">${{ number_format($reqTotal, 2, '.', ',') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="card">
                <x-empty-state icon="clipboard-list" title="No hay requisiciones registradas"
                    message="Crea una requisición o sube una cotización para comenzar." />
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $requisitions->links() }}</div>



    {{-- Reject Modal (RF-REQ-09: comentario obligatorio) --}}
    @if($showRejectModal)
        <x-modal show="showRejectModal" title="Rechazar Requisición" subtitle="Indica el motivo del rechazo (obligatorio)"
            maxWidth="md">
            <form wire:submit="confirmReject" class="p-5 space-y-4">
                <div>
                    <label class="label">Motivo del rechazo *</label>
                    <textarea wire:model="rejectionComment" class="input" rows="3"
                        placeholder="Explica por qué esta requisición fue rechazada..."></textarea>
                    @error('rejectionComment') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                </div>
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
    <div x-show="showPreviewModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        style="display: none;">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showPreviewModal = false"></div>
        <div class="relative bg-surface-card rounded-2xl shadow-xl border border-border w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden"
            x-transition>
            <div class="px-5 py-4 border-b border-border flex items-center justify-between bg-surface-card">
                <h3 class="text-h3 font-semibold text-text-primary flex items-center gap-2">
                    <i data-lucide="file-search" class="w-5 h-5 text-primary-600"></i> Vista Previa del Documento
                </h3>
                <button @click="showPreviewModal = false"
                    class="p-1.5 rounded-lg hover:bg-surface-hover text-text-muted transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="flex-1 overflow-hidden bg-surface-main p-4 relative">
                <template x-if="isImage()">
                    <img :src="previewUrl" class="w-full h-full object-contain rounded-lg">
                </template>
                <template x-if="isPdf()">
                    <iframe :src="previewUrl"
                        class="w-full h-full border border-border rounded-lg shadow-sm bg-surface-card"></iframe>
                </template>
                <template x-if="!isImage() && !isPdf()">
                    <div class="flex flex-col items-center justify-center h-full text-gray-500 gap-3">
                        <i data-lucide="file-question" class="w-12 h-12 opacity-50"></i>
                        <p class="font-medium text-body">Vista previa no disponible para este tipo de archivo.</p>
                        <a :href="previewUrl" target="_blank" class="btn-secondary text-small mt-2">
                            <i data-lucide="download" class="w-4 h-4"></i> Descargar
                        </a>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>