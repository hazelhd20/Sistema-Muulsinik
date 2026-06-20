@props(['req'])

<div class="card p-4 flex flex-col gap-3 relative transition-colors shadow-sm"
    :class="selectedRows.includes('{{ $req->id }}') ? 'bg-primary-50/50 border-primary-300' : ''"
    wire:key="req-mobile-card-{{ $req->id }}">

    {{-- Cabecera de la Fila --}}
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-3 min-w-0">
            <x-table-checkbox x-model="selectedRows" value="{{ $req->id }}" />
            <span class="font-bold text-text-primary text-base truncate">{{ $req->number ?? 'REQ-' . str_pad($req->id, 5, '0', STR_PAD_LEFT) }}</span>
            <x-status-badge :status="$req->status" :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="p-1 rounded-md text-text-muted hover:bg-surface-hover hover:text-text-primary transition-colors focus:outline-none">
                        <x-lucide-more-vertical class="w-5 h-5" />
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link as="button" type="button" @click="$dispatch('open-requisition-detail', { id: {{ $req->id }} })" icon="eye">Ver detalles</x-dropdown-link>
                    @if($req->quotations->isNotEmpty())
                        @php
                            $firstQuot = $req->quotations->first();
                            $fileUrl = route('file.preview', ['path' => $firstQuot->file_path]);
                            $mime = str_ends_with(strtolower($firstQuot->file_path), '.pdf') ? 'application/pdf' : 'image/jpeg';
                        @endphp
                        <x-dropdown-link as="button" type="button" @click="$dispatch('open-preview', { url: '{{ $fileUrl }}', type: '{{ $mime }}' })" icon="file-search">Ver cotización</x-dropdown-link>
                    @endif
                    <x-dropdown-link as="a" href="{{ route('requisiciones.pdf', $req->id) }}" target="_blank" icon="file-down">Descargar PDF</x-dropdown-link>
                    
                    @if($req->status === 'borrador' && $req->created_by === auth()->id())
                        <div class="border-t border-border my-1"></div>
                        @if(auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*'))
                            <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Aprobar Requisición', description: 'Al tener permisos de aprobación, la requisición se aprobará automáticamente.', confirmLabel: 'Aprobar', variant: 'success', action: 'submitForApproval', params: [{{ $req->id }}] })" icon="check-circle" success="true">Aprobar</x-dropdown-link>
                        @else
                            <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Solicitar Aprobación', description: 'La requisición será enviada a los aprobadores del sistema.', confirmLabel: 'Enviar a aprobación', variant: 'primary', action: 'submitForApproval', params: [{{ $req->id }}] })" icon="send">Solicitar aprobación</x-dropdown-link>
                        @endif
                    @endif
                    @if($req->status === 'pendiente' && auth()->user()->hasPermission('requisiciones.aprobar'))
                        <div class="border-t border-border my-1"></div>
                        <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Aprobar Requisición', description: 'Cambiará a estado Aprobada y se notificará al solicitante.', confirmLabel: 'Aprobar', variant: 'success', action: 'approve', params: [{{ $req->id }}] })" icon="check-circle" success="true">Aprobar</x-dropdown-link>
                        <x-dropdown-link as="button" wire:click="openRejectModal({{ $req->id }})" danger="true" icon="x-circle">Rechazar</x-dropdown-link>
                    @endif
                    @if(in_array($req->status, ['borrador', 'rechazada']))
                        <div class="border-t border-border my-1"></div>
                        <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Eliminar Requisición', description: 'Esta acción es permanente y no se puede deshacer.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteRequisition', params: [{{ $req->id }}] })" danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                    @endif
                </x-slot>
            </x-dropdown>
        </div>
    </div>

    {{-- Contenido Indentado --}}
    <div class="pl-8 flex flex-col gap-3">
        {{-- Subtítulo --}}
        <div class="text-xs text-text-muted flex flex-wrap items-center gap-x-3 gap-y-1">
            <span class="flex items-center gap-1.5 truncate">
                <x-lucide-user class="w-3.5 h-3.5 shrink-0" />
                <span class="truncate">{{ $req->creator->name ?? '—' }}</span>
            </span>
            <span class="flex items-center gap-1.5">
                <x-lucide-calendar class="w-3.5 h-3.5 shrink-0" />
                <span>{{ $req->date?->format('d/m/Y') }}</span>
            </span>
        </div>

        {{-- Datos Financieros / Detalles --}}
        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
            <div>
                <p class="text-[10px] text-text-muted uppercase font-semibold mb-0.5">Proyecto</p>
                <p class="text-small text-text-primary truncate" title="{{ $req->project->name ?? 'Sin proyecto' }}">
                    {{ $req->project->name ?? 'Sin proyecto' }}
                </p>
            </div>
            <div>
                <p class="text-[10px] text-text-muted uppercase font-semibold mb-0.5">Proveedor</p>
                @php
                    $proveedorName = $req->vendor?->supplier?->trade_name 
                        ?? $req->vendor?->name 
                        ?? $req->items->first()?->supplier?->trade_name 
                        ?? 'Sin proveedor';
                @endphp
                <p class="text-small text-text-primary truncate" title="{{ $proveedorName }}">
                    {{ $proveedorName }}
                </p>
            </div>
            <div class="col-span-2">
                <p class="text-[10px] text-text-muted uppercase font-semibold mb-0.5">Total</p>
                <p class="font-bold text-text-primary tabular-nums">
                    ${{ number_format($req->total, 2, '.', ',') }}
                </p>
            </div>
        </div>

        @if($req->status === 'rechazada' && $req->rejection_comment)
            <div class="bg-danger-50 text-danger-700 text-xs p-2.5 rounded-lg border border-danger-200 mt-1 flex items-start gap-2">
                <x-lucide-alert-circle class="w-4 h-4 shrink-0 mt-0.5" />
                <p class="leading-relaxed">{{ $req->rejection_comment }}</p>
            </div>
        @endif
    </div>
</div>
