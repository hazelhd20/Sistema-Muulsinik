<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Requisiciones</h1>
            <p class="text-sm text-text-muted">Crea, aprueba y gestiona requisiciones de materiales</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('requisiciones.upload') }}" class="btn-primary">
                <i data-lucide="scan-line" class="w-4 h-4"></i>
                Subir Cotización
            </a>
            <button wire:click="openCreateModal" class="btn-secondary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nueva Manual
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar requisición..." class="input pl-10">
        </div>
        <x-custom-select 
            wire:model.live="statusFilter" 
            :options="['borrador' => 'Borrador', 'pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada']" 
            placeholder="Todos los estados" 
            class="w-auto min-w-[160px]"
        />
        <x-custom-select 
            wire:model.live="projectFilter" 
            :options="$projects->pluck('name', 'id')->toArray()" 
            placeholder="Todos los proyectos" 
            class="w-auto min-w-[180px]"
        />
    </div>

    {{-- Requisitions list --}}
    <div class="space-y-4">
        @forelse($requisitions as $req)
            <div class="card hover:shadow-md transition-shadow">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    {{-- Left info --}}
                    <div class="flex items-start gap-4 flex-1 min-w-0">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                            {{ match($req->status) { 'borrador' => 'bg-gray-100', 'pendiente' => 'bg-amber-100', 'aprobada' => 'bg-green-100', 'rechazada' => 'bg-red-100', default => 'bg-gray-100' } }}">
                            <i data-lucide="{{ match($req->status) { 'borrador' => 'file-edit', 'pendiente' => 'clock', 'aprobada' => 'check-circle', 'rechazada' => 'x-circle', default => 'file' } }}"
                               class="w-5 h-5 {{ match($req->status) { 'borrador' => 'text-gray-600', 'pendiente' => 'text-amber-600', 'aprobada' => 'text-green-600', 'rechazada' => 'text-red-600', default => 'text-gray-600' } }}"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-semibold text-text-primary truncate">{{ $req->number ?? 'REQ-' . $req->id }}</h3>
                                @php
                                    $sColors = ['borrador' => 'badge-secondary', 'pendiente' => 'badge-warning', 'aprobada' => 'badge-success', 'rechazada' => 'badge-danger'];
                                @endphp
                                <span class="badge {{ $sColors[$req->status] ?? '' }} shrink-0">{{ ucfirst($req->status) }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-text-muted">
                                @if($req->annotations)
                                    <span class="w-full text-sm text-text-muted truncate mb-2" title="{{ $req->annotations }}">{{ $req->annotations }}</span>
                                @endif
                                <span class="flex items-center gap-1">
                                    <i data-lucide="hard-hat" class="w-3 h-3"></i>{{ $req->project->name ?? '—' }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="user" class="w-3 h-3"></i>{{ $req->creator->name ?? '—' }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="calendar" class="w-3 h-3"></i>{{ $req->date?->format('d/m/Y') }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="package" class="w-3 h-3"></i>{{ $req->items->count() }} productos
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Right: total + actions --}}
                    <div class="flex items-center gap-4 shrink-0">
                        <div class="text-right">
                            <p class="text-lg font-bold text-text-primary">${{ number_format($req->total, 2, '.', ',') }}</p>
                            <p class="text-xs text-text-muted">Estimado</p>
                        </div>

                        <div class="flex items-center gap-1">
                            @if($req->status === 'borrador')
                                <button wire:click="submitForApproval({{ $req->id }})" wire:confirm="¿Enviar esta requisición a aprobación?" class="p-2 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-600 transition" title="Enviar a aprobación">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                </button>
                            @endif
                            @if($req->status === 'pendiente')
                                <button wire:click="approve({{ $req->id }})" wire:confirm="¿Aprobar esta requisición?" class="p-2 rounded-lg bg-green-50 hover:bg-green-100 text-green-600 transition" title="Aprobar">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                </button>
                                <button wire:click="openRejectModal({{ $req->id }})" class="p-2 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 transition" title="Rechazar">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            @endif
                            @if(in_array($req->status, ['borrador', 'rechazada']))
                                <button wire:click="deleteRequisition({{ $req->id }})" wire:confirm="¿Eliminar esta requisición?"
                                    class="p-2 rounded-lg hover:bg-red-50 text-text-muted hover:text-danger transition">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Items table (collapsible) --}}
                @if($req->items->isNotEmpty())
                    <div x-data="{ open: false }" class="mt-4">
                        <button @click="open = !open" class="text-xs font-medium text-primary-600 hover:text-primary-700 flex items-center gap-1">
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="open && 'rotate-180'"></i>
                            <span x-text="open ? 'Ocultar productos' : 'Ver productos'"></span>
                        </button>
                        <div x-show="open" x-collapse class="mt-3">
                            <div class="rounded-xl border border-gray-100 overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-surface-main">
                                            <th class="text-left px-4 py-2 text-xs font-semibold text-text-muted uppercase">Producto</th>
                                            <th class="text-center px-4 py-2 text-xs font-semibold text-text-muted uppercase">Cantidad</th>
                                            <th class="text-right px-4 py-2 text-xs font-semibold text-text-muted uppercase">P. Unit.</th>
                                            <th class="text-right px-4 py-2 text-xs font-semibold text-text-muted uppercase">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($req->items as $item)
                                            <tr class="border-t border-gray-50">
                                                <td class="px-4 py-2 font-medium">{{ $item->product_name ?? $item->product?->canonical_name ?? '—' }}</td>
                                                <td class="px-4 py-2 text-center">{{ rtrim(rtrim(number_format($item->quantity, 4), '0'), '.') }} {{ $item->unit }}</td>
                                                <td class="px-4 py-2 text-right">${{ number_format($item->unit_price, 2, '.', ',') }}</td>
                                                <td class="px-4 py-2 text-right font-medium">${{ number_format($item->quantity * $item->unit_price, 2, '.', ',') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="card text-center py-12">
                <i data-lucide="clipboard-list" class="w-10 h-10 mx-auto mb-2 text-text-muted opacity-40"></i>
                <p class="text-text-muted">No hay requisiciones registradas</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $requisitions->links() }}</div>

    {{-- Create Requisition Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showCreateModal', false)"></div>
            <div class="relative bg-surface-card rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-text-primary">Nueva Requisición</h2>
                    <button wire:click="$set('showCreateModal', false)" class="p-1 rounded-lg hover:bg-surface-hover">
                        <i data-lucide="x" class="w-5 h-5 text-text-muted"></i>
                    </button>
                </div>
                <form wire:submit="createRequisition" class="p-6 space-y-5">
                    {{-- General info --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Proyecto *</label>
                            <select wire:model="reqProjectId" class="input">
                                <option value="">Seleccionar...</option>
                                @foreach($projects as $proj)
                                    <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                                @endforeach
                            </select>
                            @error('reqProjectId') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-text-primary mb-1.5">Fecha de creación *</label>
                            <input wire:model="reqDate" type="date" class="input">
                            @error('reqDate') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Anotaciones</label>
                        <textarea wire:model="reqAnnotations" class="input" rows="2" placeholder="Anotaciones de la requisición (opcional)..."></textarea>
                        @error('reqAnnotations') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    {{-- Add items --}}
                    <div class="border-t border-gray-100 pt-5">
                        <h3 class="text-sm font-semibold text-text-primary mb-3">Productos</h3>

                        {{-- Existing items --}}
                        @if(count($items) > 0)
                            <div class="rounded-xl border border-gray-100 overflow-hidden mb-4">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-surface-main">
                                            <th class="text-left px-3 py-2 text-xs font-semibold text-text-muted">Producto</th>
                                            <th class="text-center px-3 py-2 text-xs font-semibold text-text-muted">Cant.</th>
                                            <th class="text-right px-3 py-2 text-xs font-semibold text-text-muted">Precio</th>
                                            <th class="text-right px-3 py-2 text-xs font-semibold text-text-muted">Subtotal</th>
                                            <th class="px-3 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $i => $item)
                                            <tr class="border-t border-gray-50">
                                                <td class="px-3 py-2">{{ $item['name'] }}</td>
                                                <td class="px-3 py-2 text-center">{{ $item['quantity'] }} {{ $item['unit'] }}</td>
                                                <td class="px-3 py-2 text-right">${{ number_format($item['unit_price'], 2) }}</td>
                                                <td class="px-3 py-2 text-right font-medium">${{ number_format($item['quantity'] * $item['unit_price'], 2) }}</td>
                                                <td class="px-3 py-2">
                                                    <button type="button" wire:click="removeItem({{ $i }})" class="text-text-muted hover:text-danger">
                                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t border-gray-200 bg-surface-main">
                                            <td colspan="3" class="px-3 py-2 text-right text-sm font-semibold text-text-primary">Total estimado:</td>
                                            <td class="px-3 py-2 text-right text-sm font-bold text-primary-600">
                                                ${{ number_format(collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_price']), 2) }}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif

                        {{-- Add item form --}}
                        <div class="grid grid-cols-12 gap-2 items-end">
                            <div class="col-span-4">
                                <label class="block text-xs font-medium text-text-muted mb-1">Producto</label>
                                <input wire:model="itemName" type="text" class="input text-sm" placeholder="Nombre">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-text-muted mb-1">Cantidad</label>
                                <input wire:model="itemQuantity" type="number" step="0.01" class="input text-sm" placeholder="0">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-text-muted mb-1">Unidad</label>
                                <select wire:model="itemUnit" class="input text-sm">
                                    <option value="pza">Pieza</option>
                                    <option value="kg">Kg</option>
                                    <option value="m">Metro</option>
                                    <option value="m2">m²</option>
                                    <option value="m3">m³</option>
                                    <option value="lt">Litro</option>
                                    <option value="bulto">Bulto</option>
                                    <option value="rollo">Rollo</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-text-muted mb-1">Precio U.</label>
                                <input wire:model="itemPrice" type="number" step="0.01" class="input text-sm" placeholder="0.00">
                            </div>
                            <div class="col-span-2">
                                <button type="button" wire:click="addItem" class="btn-secondary w-full text-sm">
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>
                        @error('itemName') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.class="opacity-0" wire:target="createRequisition" class="transition-opacity">Crear Requisición</span>
                            <span wire:loading wire:target="createRequisition" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Reject Modal (RF-REQ-09: comentario obligatorio) --}}
    @if($showRejectModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="$set('showRejectModal', false)"></div>
            <div class="relative bg-surface-card rounded-2xl shadow-xl w-full max-w-md">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-text-primary">Rechazar Requisición</h2>
                    <p class="text-sm text-text-muted">Indica el motivo del rechazo (obligatorio)</p>
                </div>
                <form wire:submit="confirmReject" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1.5">Motivo del rechazo *</label>
                        <textarea wire:model="rejectionComment" class="input" rows="3" placeholder="Explica por qué esta requisición fue rechazada..."></textarea>
                        @error('rejectionComment') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="$set('showRejectModal', false)" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-danger text-white font-medium hover:bg-red-700 transition text-sm">
                            Confirmar Rechazo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
