<div>
    <x-page-header subtitle="Trabajos menores" title="Cotizador Rápido">
        <x-slot:actions>
            <a href="{{ route('cotizador.wizard') }}" wire:navigate class="btn-primary">
                <i data-lucide="calculator" class="w-4 h-4"></i>
                Nueva Cotización
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters --}}
    <div class="flex gap-3 mb-6">
        <x-search-input wire:model.live.debounce.50ms="search" placeholder="Buscar cotización..." />
    </div>

    {{-- Table --}}
    <div class="table-container">
        @if($budgets->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <x-sortable-header field="title" label="Título" :sortField="$sortField"
                            :sortDirection="$sortDirection" />
                        <x-sortable-header field="client" label="Cliente" :sortField="$sortField"
                            :sortDirection="$sortDirection" />
                        <x-sortable-header field="created_at" label="Fecha" :sortField="$sortField"
                            :sortDirection="$sortDirection" />
                        <th class="text-center">Ítems</th>
                        <x-sortable-header field="grand_total" label="Monto Total" :sortField="$sortField"
                            :sortDirection="$sortDirection" align="right" />
                        <th class="actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($budgets as $budget)
                        <tr>
                            <td>
                                <p class="font-medium text-text-primary">{{ $budget->title }}</p>
                                @if($budget->description)
                                    <p class="text-xs-fluid text-text-muted truncate max-w-[200px]">{{ $budget->description }}</p>
                                @endif
                            </td>
                            <td>
                                <span class="text-body text-text-secondary">{{ $budget->client ?? '—' }}</span>
                            </td>
                            <td class="text-body text-text-secondary">{{ $budget->created_at->format('d/m/Y') }}</td>
                            <td class="text-center text-body">{{ $budget->items_count }}</td>
                            <td class="text-right font-semibold text-text-primary">
                                ${{ number_format($budget->grand_total, 2, '.', ',') }}</td>
                            <td class="actions">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('cotizador.wizard', ['id' => $budget->id]) }}" wire:navigate
                                        class="btn-icon-primary" title="Editar cotización">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>
                                    <button wire:click="deleteBudget({{ $budget->id }})"
                                        wire:confirm="¿Deseas eliminar esta cotización?" class="btn-icon-danger"
                                        title="Eliminar">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <x-empty-state icon="calculator" title="No hay cotizaciones registradas"
                message="Crea una cotización rápida para trabajos menores o presupuestos ágiles." />
        @endif
    </div>

    <div class="mt-4">{{ $budgets->links() }}</div>
</div>