<div class="relative" x-data="{ open: @entangle('isOpen') }" @click.away="open = false">
    {{-- Botón de notificaciones --}}
    <button
        wire:click="toggle"
        class="relative p-1.5 rounded-md text-text-secondary hover:bg-surface-hover transition"
        title="Notificaciones"
    >
        <i data-lucide="bell" class="w-[17px] h-[17px]"></i>

        {{-- Badge de notificaciones no leídas --}}
        @if($unreadCount > 0)
            <span class="absolute top-1 right-1 min-w-[1rem] h-4 px-1 bg-danger rounded-full text-[10px] font-semibold text-white flex items-center justify-center">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @else
            <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-text-muted/50 rounded-full"></span>
        @endif
    </button>

    {{-- Dropdown de notificaciones --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute top-full right-0 mt-2 w-80 bg-surface-card rounded-xl shadow-2xl border border-border overflow-hidden z-50"
        style="display: none;"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-surface-hover">
            <h3 class="text-small font-semibold text-text-primary">Notificaciones</h3>
            @if($unreadCount > 0)
                <button
                    wire:click="markAllAsRead"
                    class="text-xs-fluid text-primary-600 hover:text-primary-700 font-medium transition-colors"
                >
                    Marcar todas como leídas
                </button>
            @endif
        </div>

        {{-- Lista de notificaciones --}}
        <div class="max-h-[60vh] overflow-y-auto">
            @if(empty($notifications))
                <div class="p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-surface-hover flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="bell-off" class="w-5 h-5 text-text-muted"></i>
                    </div>
                    <p class="text-small text-text-secondary">No hay notificaciones</p>
                    <p class="text-xs-fluid text-text-muted mt-1">Las notificaciones aparecerán aquí</p>
                </div>
            @else
                @foreach($notifications as $notification)
                    @php
                        $colorClasses = [
                            'primary' => 'bg-primary-50 text-primary-600',
                            'success' => 'bg-emerald-50 text-emerald-600',
                            'warning' => 'bg-amber-50 text-amber-600',
                            'danger' => 'bg-red-50 text-red-600',
                        ][$notification['color']] ?? 'bg-primary-50 text-primary-600';

                        $isUnread = is_null($notification['read_at']);
                    @endphp

                    <div
                        class="flex gap-3 px-4 py-3 border-b border-border hover:bg-surface-hover transition-colors {{ $isUnread ? 'bg-primary-50/30' : '' }}"
                    >
                        {{-- Icono --}}
                        <div class="w-9 h-9 rounded-lg {{ $colorClasses }} flex items-center justify-center shrink-0">
                            <i data-lucide="{{ $notification['icon'] }}" class="w-4 h-4"></i>
                        </div>

                        {{-- Contenido --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-small font-medium text-text-primary leading-tight">
                                    {{ $notification['title'] }}
                                </p>
                                @if($isUnread)
                                    <span class="w-2 h-2 bg-primary-500 rounded-full shrink-0 mt-1"></span>
                                @endif
                            </div>
                            <p class="text-xs-fluid text-text-secondary mt-0.5 leading-relaxed">
                                {{ $notification['message'] }}
                            </p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs-fluid text-text-muted">{{ $notification['created_at'] }}</span>
                                <div class="flex items-center gap-2">
                                    <a
                                        href="{{ $notification['action_url'] }}"
                                        wire:navigate
                                        wire:click="markAsRead('{{ $notification['id'] }}')"
                                        class="text-xs-fluid font-medium text-primary-600 hover:text-primary-700 transition-colors"
                                    >
                                        {{ $notification['action_text'] }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Footer --}}
        @if(!empty($notifications))
            <div class="px-4 py-2 bg-surface-hover border-t border-border text-center">
                <a
                    href="{{ url('/notificaciones') }}"
                    wire:navigate
                    class="text-small text-primary-600 hover:text-primary-700 font-medium transition-colors"
                >
                    Ver todas las notificaciones
                </a>
            </div>
        @endif
    </div>

    {{-- Polling cada 60 segundos para actualizar --}}
    <div wire:poll.60s="loadNotifications"></div>
</div>
