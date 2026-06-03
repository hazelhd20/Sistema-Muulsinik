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
        <div class="relative flex-1 max-w-md" x-data="{ focused: false }">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted"></i>
            <input wire:model.live.debounce.50ms="search" type="search" placeholder="Buscar cotización..."
                class="input pl-10 pr-10" @focus="focused = true" @blur="focused = false">
            <button x-show="$wire.search" x-transition @click="$wire.search = ''" type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded hover:bg-surface-hover text-text-muted"
                style="display: none;">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <x-sortable-header field="title" label="Título" :sortField="$sortField" :sortDirection="$sortDirection" />
                    <x-sortable-header field="client" label="Cliente" :sortField="$sortField" :sortDirection="$sortDirection" />
                    <x-sortable-header field="created_at" label="Fecha" :sortField="$sortField" :sortDirection="$sortDirection" />
                    <th class="text-center">Ítems</th>
                    <x-sortable-header field="grand_total" label="Monto Total" :sortField="$sortField" :sortDirection="$sortDirection" align="right" />
                    <th class="actions">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($budgets as $budget)
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
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty-state icon="calculator" title="No hay cotizaciones registradas"
                                message="Crea una cotización rápida para trabajos menores o presupuestos ágiles." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $budgets->links() }}</div>
</div>