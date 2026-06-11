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
            <div class="table-container hidden md:block">
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

            {{-- Tarjetas Móviles (Mobile View) --}}
            @if($budgets->isNotEmpty())
            <div class="md:hidden flex flex-col gap-4 mt-2">
                @foreach($budgets as $budget)
                    <div class="card p-4 flex flex-col gap-3 relative overflow-hidden transition-colors group">
                        
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <span class="font-bold text-text-primary text-body truncate block">{{ $budget->title }}</span>
                                <p class="text-xs-fluid text-text-secondary mt-1 truncate">Cliente: {{ $budget->client ?? '—' }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="font-bold text-text-primary text-body block">${{ number_format($budget->grand_total, 2, '.', ',') }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs-fluid text-text-muted bg-surface-main p-3 rounded-xl border border-border/50">
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 shrink-0"></i>
                                <span>{{ $budget->created_at->format('d/m/Y') }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 justify-end">
                                <i data-lucide="list" class="w-3.5 h-3.5 shrink-0"></i>
                                <span>{{ $budget->items_count }} ítems</span>
                            </div>
                            
                            @if($budget->description)
                                <div class="col-span-2 flex items-start gap-1.5 mt-1">
                                    <i data-lucide="align-left" class="w-3.5 h-3.5 shrink-0 mt-0.5"></i>
                                    <span class="line-clamp-2">{{ $budget->description }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="flex justify-end gap-1 pt-3 border-t border-border/50 mt-1">
                            <x-button href="{{ route('cotizador.wizard', ['id' => $budget->id]) }}" variant="icon-primary" icon="edit-2" class="text-xs-fluid w-8 h-8" wire:navigate />
                            <x-button wire:click="deleteBudget({{ $budget->id }})" wire:confirm="¿Eliminar esta cotización? Esta acción no puede deshacerse." variant="icon-danger" icon="trash-2" class="text-xs-fluid w-8 h-8" />
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="search, previousPage, nextPage, gotoPage"
            class="hidden absolute inset-0 w-full z-10 bg-surface-main">
            <div class="table-container hidden md:block">
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

            {{-- Skeletons Móviles --}}
            <div class="md:hidden flex flex-col gap-4 mt-2">
                @for($i = 0; $i < 4; $i++)
                    <div class="card p-4 flex flex-col gap-3 relative overflow-hidden bg-surface-main">
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <x-skeleton class="h-5 w-32 rounded" />
                                <x-skeleton class="h-3 w-24 rounded mt-1.5" />
                            </div>
                            <x-skeleton class="h-5 w-20 rounded" />
                        </div>
                        <div class="flex justify-between items-center bg-surface-hover/50 p-3 rounded-xl border border-border/50">
                            <x-skeleton class="h-4 w-24 rounded" />
                            <x-skeleton class="h-4 w-16 rounded" />
                        </div>
                        <div class="flex justify-end gap-1 pt-3 border-t border-border/50 mt-1">
                            <x-skeleton class="h-8 w-8 rounded" />
                            <x-skeleton class="h-8 w-8 rounded" />
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $budgets->links() }}</div>
</div>