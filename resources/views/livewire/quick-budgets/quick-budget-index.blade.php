<div>
    <x-page-header subtitle="Trabajos menores" title="Cotizador Rápido">
        <x-slot:actions>
            <x-button href="{{ route('cotizador.wizard') }}" variant="primary" icon="calculator" wire:navigate>
                Nueva Cotización
            </x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters --}}
    <div class="flex gap-3 mb-6">
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Buscar cotización..." />
    </div>

    {{-- Table --}}
    <div class="relative min-h-[200px]">
        <div wire:loading.class="hidden" wire:target="search, previousPage, nextPage, gotoPage" class="w-full">
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
                                            <x-button href="{{ route('cotizador.wizard', ['id' => $budget->id]) }}" variant="icon-primary" icon="edit-2" title="Editar cotización" wire:navigate />
                                            <x-button wire:click="deleteBudget({{ $budget->id }})"
                                                wire:confirm="¿Eliminar esta cotización? Esta acción no puede deshacerse." variant="icon-danger" icon="trash-2"
                                                title="Eliminar" />
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
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="search, previousPage, nextPage, gotoPage"
            class="hidden absolute inset-0 w-full z-10 bg-surface-main">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th class="text-center">Ítems</th>
                            <th class="text-right">Monto Total</th>
                            <th class="actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 0; $i < 5; $i++)
                            <tr>
                                <td>
                                    <x-skeleton class="h-4  rounded w-40 mb-1" />
                                    <x-skeleton class="h-3  rounded w-28" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-24" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-20" />
                                </td>
                                <td class="text-center">
                                    <x-skeleton class="h-4  rounded w-8 mx-auto" />
                                </td>
                                <td>
                                    <x-skeleton class="h-4  rounded w-16 ml-auto" />
                                </td>
                                <td class="actions">
                                    <div class="flex items-center justify-end gap-1">
                                        <x-skeleton class="w-8 h-8  rounded" />
                                        <x-skeleton class="w-8 h-8  rounded" />
                                    </div>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $budgets->links() }}</div>
</div>