<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-h1 font-bold text-text-primary">Notificaciones</h1>
            <p class="text-body text-text-secondary mt-1">Historial de todas tus notificaciones</p>
        </div>
        <div class="flex items-center gap-2">
            @if(!$this->notifications->whereNull('read_at')->isEmpty())
                <x-button
                    wire:click="markAllAsRead"
                    variant="secondary"
                >
                    <svg class="w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12l5 5L20 4"></path>
                        <path d="m6 12 5 5 8-8"></path>
                    </svg>
                    <span>Marcar todas como leídas</span>
                </x-button>
            @endif

            @if(!$this->notifications->isEmpty())
                <x-button
                    wire:click="deleteAll"
                    wire:confirm="¿Eliminar todas las notificaciones? Esta acción no puede deshacerse."
                    variant="secondary"
                    class="text-danger hover:bg-danger-light hover:border-danger/50"
                >
                    <svg class="w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                    <span>Eliminar todas</span>
                </x-button>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <div class="card p-3">
        <div class="flex flex-wrap gap-2">
            <button
                wire:click="$set('filter', 'all')"
                class="px-3 py-1.5 rounded-lg text-small font-medium transition-colors {{ $filter === 'all' ? 'bg-primary-600 text-white' : 'bg-surface-hover text-text-secondary hover:bg-border' }}"
            >
                Todas
            </button>
            <button
                wire:click="$set('filter', 'unread')"
                class="px-3 py-1.5 rounded-lg text-small font-medium transition-colors {{ $filter === 'unread' ? 'bg-primary-600 text-white' : 'bg-surface-hover text-text-secondary hover:bg-border' }}"
            >
                No leídas
            </button>
            <button
                wire:click="$set('filter', 'read')"
                class="px-3 py-1.5 rounded-lg text-small font-medium transition-colors {{ $filter === 'read' ? 'bg-primary-600 text-white' : 'bg-surface-hover text-text-secondary hover:bg-border' }}"
            >
                Leídas
            </button>
        </div>
    </div>

    {{-- Notifications List --}}
    <div class="relative">
        <div wire:loading.class="hidden" wire:target="filter, previousPage, nextPage, gotoPage, markAllAsRead, deleteAll, markAsRead, markAsUnread, delete" class="w-full">
            <div class="card p-0">
                @if($this->notifications->isEmpty())
                    <x-empty-state icon="bell-off" title="No hay notificaciones" />
                @else
                    <div class="divide-y divide-border">
                        @foreach($this->notifications as $notification)
                            @php
                                $data = $notification->data;
                                $colorClasses = [
                                    'primary' => 'bg-primary-50 text-primary-600',
                                    'success' => 'bg-success-light text-success',
                                    'warning' => 'bg-warning-light text-warning',
                                    'danger'  => 'bg-danger-light text-danger',
                                ][$data['color'] ?? 'primary'] ?? 'bg-primary-50 text-primary-600';

                                $isUnread = is_null($notification->read_at);
                            @endphp

                            <div class="flex gap-4 px-5 py-4 hover:bg-surface-hover transition-colors {{ $isUnread ? 'bg-primary-50/20' : '' }}">
                                {{-- Icon --}}
                                <div class="w-11 h-11 rounded-xl {{ $colorClasses }} flex items-center justify-center shrink-0">
                                    <x-dynamic-component :component="'lucide-' . $data['icon'] ?? 'bell'" class="w-5 h-5" />
                                </div>

                                {{-- Content --}}
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h4 class="text-body font-semibold text-text-primary">
                                                {{ $data['title'] ?? 'Notificación' }}
                                                @if($isUnread)
                                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                                        Nueva
                                                    </span>
                                                @endif
                                            </h4>
                                            <p class="text-small text-text-secondary mt-1">
                                                {{ $data['message'] ?? '' }}
                                            </p>
                                        </div>
                                        <span class="text-xs text-text-muted shrink-0 whitespace-nowrap">
                                            {{ $notification->created_at->locale('es')->diffForHumans() }}
                                        </span>
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex items-center gap-3 mt-3">
                                        <a
                                            href="{{ $data['action_url'] ?? '#' }}"
                                            @if(!str_contains($data['action_url'] ?? '', '/storage/exports/')) wire:navigate @else download @endif
                                            wire:click="markAsRead('{{ $notification->id }}')"
                                            class="text-small font-medium text-primary-600 hover:text-primary-700 transition-colors"
                                        >
                                            {{ $data['action_text'] ?? 'Ver detalle' }}
                                        </a>

                                        <div class="h-3 w-px bg-border"></div>

                                        @if($isUnread)
                                            <button
                                                wire:click="markAsRead('{{ $notification->id }}')"
                                                class="text-small font-medium text-text-muted hover:text-text-secondary transition-colors"
                                            >
                                                Marcar como leída
                                            </button>
                                        @else
                                            <button
                                                wire:click="markAsUnread('{{ $notification->id }}')"
                                                class="text-small font-medium text-text-muted hover:text-text-secondary transition-colors"
                                            >
                                                Marcar como no leída
                                            </button>
                                        @endif

                                        <div class="h-3 w-px bg-border"></div>

                                        <button
                                            wire:click="delete('{{ $notification->id }}')"
                                            class="text-small font-medium text-text-muted hover:text-danger transition-colors"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if($this->notifications->hasPages())
                        <div class="px-5 py-4 border-t border-border">
                            {{ $this->notifications->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Skeleton Loader --}}
        <div wire:loading.class.remove="hidden" wire:target="filter, previousPage, nextPage, gotoPage, markAllAsRead, deleteAll, markAsRead, markAsUnread, delete"
            class="hidden absolute inset-0 w-full z-10 bg-surface-main">
            <div class="card p-0 divide-y divide-border">
                @for($i = 0; $i < 4; $i++)
                    <div class="flex gap-4 px-5 py-4">
                        {{-- Icon --}}
                        <x-skeleton class="w-11 h-11 rounded-xl  shrink-0" />

                        {{-- Content --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-4">
                                <div class="w-full">
                                    <x-skeleton class="h-4  rounded w-48 mb-2" />
                                    <x-skeleton class="h-3.5  rounded w-3/4" />
                                </div>
                                <x-skeleton class="h-3  rounded w-16 shrink-0" />
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-3 mt-4">
                                <x-skeleton class="h-3.5  rounded w-16" />
                                <div class="h-3 w-px bg-border"></div>
                                <x-skeleton class="h-3.5  rounded w-28" />
                                <div class="h-3 w-px bg-border"></div>
                                <x-skeleton class="h-3.5  rounded w-12" />
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>
</div>
