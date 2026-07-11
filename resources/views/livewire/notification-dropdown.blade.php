<div class="relative" x-data="{ open: false, id: $id('dropdown-menu') }" x-id="['dropdown-menu']" @close.stop="open = false" @dropdown-opened.window="if ($event.detail.id !== id) open = false">
    {{-- Botón de notificaciones (wire:ignore para prevenir parpadeo del icono de campana) --}}
    <button @click="open = !open; if (open) $dispatch('dropdown-opened', { id })" x-ref="trigger" wire:ignore.self
        class="group relative inline-flex items-center justify-center w-9 h-9 p-2 rounded-lg text-text-muted icon-btn-hover transition-all duration-200 ease-out active:scale-95 cursor-pointer"
        title="Notificaciones">
        <x-lucide-bell class="w-5 h-5 transition-transform duration-200 group-hover:rotate-12" />

        {{-- Badge de notificaciones no leídas --}}
        <div class="absolute top-1 right-1" wire:key="unread-badge">
            @if($unreadCount > 0)
                <span
                    class="min-w-[18px] h-[18px] px-1 bg-danger rounded-full text-2xs font-bold text-white flex items-center justify-center shadow-sm leading-none animate-scale-in">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </div>
    </button>

    {{-- Dropdown de notificaciones --}}
    <div x-show="open" x-cloak
        @click.outside="if (! $refs.trigger.contains($event.target)) open = false"
        x-anchor.bottom-end.offset.4="$refs.trigger"
        x-transition:enter="transition-premium"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition-premium"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-[100] w-80 bg-surface-card rounded-xl shadow-xl border border-border overflow-hidden"
        style="display: none;">
        {{-- Header --}}
        <div class="dropdown-header">
            <h3 class="dropdown-header-title">Notificaciones</h3>
            @if($unreadCount > 0)
                <x-button wire:click="markAllAsRead" variant="link" class="!text-xs-fluid !min-h-0">
                    Marcar todas como leídas
                </x-button>
            @endif
        </div>

        {{-- Lista de notificaciones --}}
        <div class="max-h-[60vh] overflow-y-auto">
            @if(empty($notifications))
                <div class="py-4">
                    <x-empty-state icon="bell-off" title="No hay notificaciones" />
                </div>
            @else
                @foreach($notifications as $notification)
                    @php
                        $colorClasses = [
                            'primary' => 'bg-primary-50 text-primary-600',
                            'success' => 'bg-success-light text-success',
                            'warning' => 'bg-warning-light text-warning',
                            'danger' => 'bg-danger-light text-danger',
                        ][$notification['color']] ?? 'bg-primary-50 text-primary-600';

                        $isUnread = is_null($notification['read_at']);
                    @endphp

                    <div wire:key="notification-{{ $notification['id'] }}"
                        class="flex gap-3 px-4 py-3 border-b border-border {{ $isUnread ? 'bg-primary-50/30' : '' }}">
                        {{-- Icono --}}
                        <div class="w-9 h-9 rounded-lg {{ $colorClasses }} flex items-center justify-center shrink-0">
                            <x-dynamic-component :component="'lucide-' . $notification['icon']" class="w-4 h-4" />
                        </div>

                        {{-- Contenido --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-small font-medium text-text-primary leading-tight">
                                    {{ $notification['title'] }}
                                </p>
                                @if($isUnread)
                                    <x-pulse-indicator color="primary" class="shrink-0 mt-1" />
                                @endif
                            </div>
                            <p class="text-xs-fluid text-text-secondary mt-0.5 leading-relaxed">
                                {{ $notification['message'] }}
                            </p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs-fluid text-text-muted" 
                                    x-data="{ 
                                        time: '{{ $notification['created_at_iso'] }}', 
                                        relative: '{{ $notification['created_at'] }}' 
                                    }"
                                    x-init="setInterval(() => {
                                        const seconds = Math.floor((new Date() - new Date(time)) / 1000);
                                        if(seconds < 60) relative = 'hace unos segundos';
                                        else if(seconds < 3600) relative = 'hace ' + Math.floor(seconds/60) + (Math.floor(seconds/60) === 1 ? ' minuto' : ' minutos');
                                        else if(seconds < 86400) relative = 'hace ' + Math.floor(seconds/3600) + (Math.floor(seconds/3600) === 1 ? ' hora' : ' horas');
                                        else relative = 'hace ' + Math.floor(seconds/86400) + (Math.floor(seconds/86400) === 1 ? ' día' : ' días');
                                    }, 60000)"
                                    x-text="relative">
                                    {{ $notification['created_at'] }}
                                </span>
                                <div class="flex items-center gap-2">
                                    @if(($notification['type'] ?? '') === 'export_completed')
                                        {{-- Exportaciones: abrir en pestaña nueva para no romper el historial de navegación --}}
                                        <x-button href="{{ $notification['action_url'] }}" 
                                            target="_blank"
                                            wire:click="markAsRead('{{ $notification['id'] }}')"
                                            variant="link" class="!text-xs-fluid !min-h-0">
                                            {{ $notification['action_text'] }}
                                        </x-button>
                                    @else
                                        <x-button href="{{ $notification['action_url'] }}" 
                                            wire:navigate
                                            wire:click="markAsRead('{{ $notification['id'] }}')"
                                            variant="link" class="!text-xs-fluid !min-h-0">
                                            {{ $notification['action_text'] }}
                                        </x-button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Footer --}}
        @if(!empty($notifications))
            <div class="dropdown-footer justify-center">
                <x-button href="{{ url('/notificaciones') }}" wire:navigate
                    variant="link" class="!text-small !min-h-0">
                    Ver todas las notificaciones
                </x-button>
            </div>
        @endif
    </div>

</div>
