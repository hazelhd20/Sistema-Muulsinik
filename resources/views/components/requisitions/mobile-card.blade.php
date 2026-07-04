@props(['req'])

<x-card class="p-0 flex flex-col relative transition-colors overflow-hidden"
    x-bind:class="selectedRows.includes('{{ $req->id }}') ? 'bg-primary-50/50 border-primary-300 ring-1 ring-primary-300' : ''"
    wire:key="req-mobile-card-{{ $req->id }}">

    {{-- Cabecera de la Fila --}}
    <div class="flex items-center justify-between gap-2 p-4 pb-3 border-b border-border/40 bg-surface-card">
        <div class="flex items-center gap-3 min-w-0">
            <x-table-checkbox x-model="selectedRows" value="{{ $req->id }}" />
            <span
                class="font-bold text-text-primary text-h3 truncate">{{ $req->number ?? 'REQ-' . str_pad($req->id, 5, '0', STR_PAD_LEFT) }}</span>
            <x-status-badge :status="$req->status" :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link as="button" type="button"
                        @click="$dispatch('open-requisition-detail', { id: {{ $req->id }} })" icon="eye">Ver
                        detalles</x-dropdown-link>
                    @if($req->quotations->isNotEmpty())
                        @php
                            $firstQuot = $req->quotations->first();
                            $fileUrl = route('file.preview', ['path' => $firstQuot->file_path]);
                            $mime = str_ends_with(strtolower($firstQuot->file_path), '.pdf') ? 'application/pdf' : 'image/jpeg';
                        @endphp
                        <x-dropdown-link as="button" type="button"
                            @click="$dispatch('open-preview', { url: '{{ $fileUrl }}', type: '{{ $mime }}' })"
                            icon="file-search">Ver cotización</x-dropdown-link>
                    @endif
                    <x-dropdown-link as="a" href="{{ route('requisiciones.pdf', $req->id) }}" target="_blank"
                        icon="file-down">Descargar PDF</x-dropdown-link>

                    @if(in_array($req->status, ['borrador', 'rechazada']))
                        @if(auth()->user()->hasPermission('requisiciones.eliminar') || auth()->user()->hasPermission('requisiciones.editar') || auth()->user()->hasPermission('*'))
                            <x-dropdown-link as="button" type="button"
                                @click="$dispatch('confirm-action', { title: 'Eliminar Requisición', description: 'Esta acción es permanente y no se puede deshacer.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteRequisition', params: [{{ $req->id }}] })"
                                danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                        @endif
                    @endif
                </x-slot>
            </x-dropdown>
        </div>
    </div>

    {{-- Contenido Principal --}}
    <div class="p-4 flex flex-col gap-4">
        {{-- Subtítulo --}}
        <div class="text-small text-text-muted flex flex-wrap items-center gap-x-4 gap-y-2">
            <span class="flex items-center gap-1.5 truncate">
                <x-lucide-user class="w-3.5 h-3.5 shrink-0 opacity-70" />
                <span class="truncate font-medium">{{ $req->creator->name ?? '—' }}</span>
            </span>
            <span class="flex items-center gap-1.5">
                <x-lucide-calendar class="w-3.5 h-3.5 shrink-0 opacity-70" />
                <span class="font-medium">{{ $req->date?->format('d/m/Y') }}</span>
            </span>
        </div>

        {{-- Datos Financieros / Detalles --}}
        <div class="grid grid-cols-2 gap-x-4 gap-y-4 pt-3 border-t border-border/40">
            <div>
                <p class="text-xs-fluid text-text-muted uppercase font-semibold tracking-wider mb-1">Proyecto</p>
                <p class="text-body font-medium text-text-primary truncate"
                    title="{{ $req->project->name ?? 'Sin proyecto' }}">
                    {{ $req->project->name ?? 'Sin proyecto' }}
                </p>
            </div>
            <div>
                <p class="text-xs-fluid text-text-muted uppercase font-semibold tracking-wider mb-1">Proveedor</p>
                @php
                    $proveedorName = $req->vendor?->supplier?->trade_name
                        ?? $req->vendor?->name
                        ?? $req->items->first()?->supplier?->trade_name
                        ?? 'Sin proveedor';
                @endphp
                <p class="text-body font-medium text-text-primary truncate" title="{{ $proveedorName }}">
                    {{ $proveedorName }}
                </p>
            </div>
        </div>

        <div class="flex items-center justify-between pt-3 mt-1 border-t border-border/40">
            <p class="text-xs-fluid font-semibold text-text-muted uppercase tracking-wider">Total</p>
            <p class="font-bold text-h2 text-text-primary tabular-nums">
                ${{ number_format($req->total, 2, '.', ',') }}
            </p>
        </div>

        @if($req->status === 'rechazada' && $req->rejection_comment)
            <div
                class="bg-danger-light text-danger-active border border-danger-border text-small p-3 rounded-lg mt-2 flex items-start gap-2.5">
                <x-lucide-alert-circle class="w-4 h-4 shrink-0 mt-0.5 text-danger" />
                <p class="leading-relaxed font-medium">{{ $req->rejection_comment }}</p>
            </div>
        @endif
    </div>

    {{-- Barra de Acciones --}}
    @if(in_array($req->status, ['pendiente', 'borrador']))
        <div class="px-4 py-3 bg-surface-card border-t border-border/40 flex items-center justify-end gap-2.5">
            @if($req->status === 'pendiente' && (auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*')))
                <x-button as="button" type="button" size="sm" variant="secondary" icon="x-circle"
                    @click.stop="$wire.openRejectModal({{ $req->id }})">
                    Rechazar
                </x-button>
                <x-button as="button" type="button" size="sm" variant="success" icon="check-circle"
                    @click.stop="$dispatch('confirm-action', { title: 'Aprobar Requisición', description: 'Cambiará a estado Aprobada y se notificará al solicitante.', confirmLabel: 'Aprobar', variant: 'success', action: 'approve', params: [{{ $req->id }}] })">
                    Aprobar
                </x-button>
            @elseif($req->status === 'borrador' && $req->created_by === auth()->id())
                @if(auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*'))
                    <x-button as="button" type="button" size="sm" variant="success" icon="check-circle"
                        @click.stop="$dispatch('confirm-action', { title: 'Aprobar Requisición', description: 'Al tener permisos de aprobación, la requisición se aprobará automáticamente.', confirmLabel: 'Aprobar', variant: 'success', action: 'submitForApproval', params: [{{ $req->id }}] })">
                        Aprobar
                    </x-button>
                @else
                    <x-button as="button" type="button" size="sm" variant="primary" icon="send"
                        @click.stop="$dispatch('confirm-action', { title: 'Solicitar Aprobación', description: 'La requisición será enviada a los aprobadores del sistema.', confirmLabel: 'Enviar a aprobación', variant: 'primary', action: 'submitForApproval', params: [{{ $req->id }}] })">
                        Solicitar aprobación
                    </x-button>
                @endif
            @endif
        </div>
    @endif
</x-card>