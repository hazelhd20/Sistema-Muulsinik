<div class="relative" x-data="{ open: false, id: $id('dropdown-menu') }" x-id="['dropdown-menu']" @close.stop="open = false" @dropdown-opened.window="if ($event.detail.id !== id) open = false">
    {{-- Botón de notificaciones (wire:ignore para prevenir parpadeo del icono de campana) --}}
    <button @click="open = !open; if (open) $dispatch('dropdown-opened', { id })" x-ref="trigger" wire:ignore.self
        class="relative p-1.5 rounded-md text-text-muted hover:text-text-primary hover:bg-black/5 transition-colors"
        title="Notificaciones">
        <x-lucide-bell class="w-4 h-4" />

        {{-- Badge de notificaciones no leídas --}}
        <div class="absolute -top-0.5 -right-0.5" wire:key="unread-badge">
            @if($unreadCount > 0)
                <span
                    class="min-w-[14px] h-[14px] px-0.5 bg-danger rounded-full text-xs-fluid font-bold text-white flex items-center justify-center shadow-sm leading-none">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </div>
    </button>

    {{-- Dropdown de notificaciones --}}
    <template x-teleport="body">
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
                <button wire:click="markAllAsRead"
                    class="text-xs-fluid text-primary-600 hover:text-primary-700 font-medium transition-colors">
                    Marcar todas como leídas
                </button>
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

                    <div
                        class="flex gap-3 px-4 py-3 border-b border-border hover:bg-surface-hover transition-colors {{ $isUnread ? 'bg-primary-50/30' : '' }}">
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
                                    <span class="w-2 h-2 bg-primary-500 rounded-full shrink-0 mt-1"></span>
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
                                    <a href="{{ $notification['action_url'] }}" 
                                        @if(!str_contains($notification['action_url'], '/storage/exports/')) wire:navigate @else download @endif
                                        wire:click="markAsRead('{{ $notification['id'] }}')"
                                        class="text-xs-fluid font-medium text-primary-600 hover:text-primary-700 transition-colors">
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
            <div class="dropdown-footer justify-center">
                <a href="{{ url('/notificaciones') }}" wire:navigate
                    class="text-small text-primary-600 hover:text-primary-700 font-medium transition-colors">
                    Ver todas las notificaciones
                </a>
            </div>
        @endif
    </div>
    </template>
</div>
