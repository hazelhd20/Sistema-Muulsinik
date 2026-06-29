@props(['req'])

<tr wire:key="requisition-row-{{ $req->id }}"
    class="group hover:bg-surface-hover transition-colors duration-150"
    :class="selectedRows.includes('{{ $req->id }}') ? 'bg-primary-50/50' : ''">
    
    <td class="actions text-center pl-4 pr-2" @click.stop="$event.stopPropagation()">
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
    <td class="truncate max-w-0 text-text-secondary" title="{{ $req->supplier_name }}">
        {{ $req->supplier_name }}
    </td>
    <td class="text-right font-semibold tabular-nums text-text-primary numeric">
        ${{ number_format($req->total, 2, '.', ',') }}
    </td>
    <td class="py-3">
        <x-status-badge :status="$req->status" :map="['borrador' => 'secondary', 'pendiente' => 'warning', 'aprobada' => 'success', 'rechazada' => 'danger']" />
        @if($req->status === 'rechazada' && $req->rejection_comment)
            <p class="text-[10px] text-danger mt-1 truncate max-w-0" title="{{ $req->rejection_comment }}">
                {{ $req->rejection_comment }}
            </p>
        @endif
    </td>
    <td class="actions pr-4 py-3" @click.stop="$event.stopPropagation()">
        <div class="flex items-center justify-end">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <x-button variant="icon" icon="more-vertical" aria-label="Opciones" title="Opciones" />
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
                        @if(auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*'))
                            <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Aprobar Requisición', description: 'Al tener permisos de aprobación, la requisición se aprobará automáticamente.', confirmLabel: 'Aprobar', variant: 'success', action: 'submitForApproval', params: [{{ $req->id }}] })" icon="check-circle" success="true">Aprobar</x-dropdown-link>
                        @else
                            <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Solicitar Aprobación', description: 'La requisición será enviada a los aprobadores del sistema.', confirmLabel: 'Enviar a aprobación', variant: 'primary', action: 'submitForApproval', params: [{{ $req->id }}] })" icon="send">Solicitar aprobación</x-dropdown-link>
                        @endif
                    @endif

                    @if($req->status === 'pendiente' && (auth()->user()->hasPermission('requisiciones.aprobar') || auth()->user()->hasPermission('*')))
                        <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Aprobar Requisición', description: 'Cambiará a estado Aprobada y se notificará al solicitante.', confirmLabel: 'Aprobar', variant: 'success', action: 'approve', params: [{{ $req->id }}] })" icon="check-circle" success="true">Aprobar</x-dropdown-link>
                        <x-dropdown-link as="button" wire:click="openRejectModal({{ $req->id }})" danger="true" icon="x-circle">Rechazar</x-dropdown-link>
                    @endif

                    @if(in_array($req->status, ['borrador', 'rechazada']))
                        <x-dropdown-link as="button" type="button" @click="$dispatch('confirm-action', { title: 'Eliminar Requisición', description: 'Esta acción es permanente y no se puede deshacer.', confirmLabel: 'Eliminar', variant: 'danger', action: 'deleteRequisition', params: [{{ $req->id }}] })" danger="true" icon="trash-2">Eliminar</x-dropdown-link>
                    @endif
                </x-slot>
            </x-dropdown>
        </div>
    </td>
</tr>
