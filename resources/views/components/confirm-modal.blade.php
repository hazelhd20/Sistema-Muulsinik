{{--
┌─────────────────────────────────────────────────────────────────┐
│  x-confirm-modal — Diálogo de confirmación reutilizable         │
│  Reemplaza wire:confirm (diálogo nativo del navegador)          │
├─────────────────────────────────────────────────────────────────┤
│  Incluir UNA vez al final de cada vista Livewire que lo use:    │
│    <x-confirm-modal />                                          │
│                                                                 │
│  Disparar desde cualquier elemento Alpine:                      │
│    @click="$dispatch('confirm-action', {                        │
│        title:        'Eliminar Requisición',                    │
│        description:  'CEN1-REQ0021 · Esta acción es permanente.',│
│        confirmLabel: 'Eliminar',                                │
│        cancelLabel:  'Cancelar',      ← opcional               │
│        variant:      'danger',        ← danger|warning|primary|success
│        action:       'deleteRequisition',  ← método Livewire   │
│        params:       [21]             ← argumentos del método  │
│    })"                                                          │
└─────────────────────────────────────────────────────────────────┘
--}}

<div
    x-data="{
        show:         false,
        title:        '',
        description:  '',
        confirmLabel: 'Confirmar',
        cancelLabel:  'Cancelar',
        variant:      'danger',
        action:       '',
        params:       [],
        loading:      false,

        get iconName() {
            return {
                danger:  'alert-triangle',
                warning: 'alert-circle',
                primary: 'help-circle',
                success: 'check-circle',
            }[this.variant] ?? 'alert-triangle';
        },

        get confirmBtnClass() {
            return {
                danger:  'btn-danger',
                warning: 'btn-warning',
                success: 'btn-success',
                primary: 'btn-primary',
            }[this.variant] ?? 'btn-primary';
        },

        open(payload) {
            this.title        = payload.title        ?? 'Confirmar acción';
            this.description  = payload.description  ?? '';
            this.confirmLabel = payload.confirmLabel ?? 'Confirmar';
            this.cancelLabel  = payload.cancelLabel  ?? 'Cancelar';
            this.variant      = payload.variant      ?? 'danger';
            this.action       = payload.action       ?? '';
            this.params       = payload.params       ?? [];
            this.onConfirmCallback = payload.onConfirmCallback ?? null;
            this.loading      = false;
            this.show         = true;
        },

        async execute() {
            if (this.loading) return;
            this.loading = true;
            try {
                if (this.onConfirmCallback) {
                    await this.onConfirmCallback();
                } else if (this.action) {
                    await $wire[this.action](...this.params);
                }
            } catch(e) {
                console.error('[confirm-modal]', e);
            } finally {
                this.loading = false;
                this.show    = false;
            }
        },

        close() {
            if (this.loading) return;
            this.show = false;
        }
    }"
    @confirm-action.window="open($event.detail)"
    x-show="show"
    x-cloak
    x-trap.noscroll.inert="show"
    @keydown.escape.window="close()"
    class="fixed inset-0 z-[70] flex items-center justify-center p-4 sm:p-6"
    role="dialog"
    aria-modal="true"
    :aria-label="title">

    {{-- ── Backdrop ── --}}
    <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"
         x-transition:enter="transition-premium"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="close()"
         aria-hidden="true"></div>

    {{-- ── Panel ── --}}
    <div class="relative bg-surface-card rounded-xl shadow-2xl border border-border w-full max-w-sm"
         x-transition:enter="transition-premium"
         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-1">

        <div class="p-5">

            {{-- ── Icon + Texto ── --}}
            <div class="flex items-start gap-3.5 mb-5">

                {{-- Contenedor de icono con color semántico --}}
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                     :class="{
                         'bg-danger-light  text-danger':    variant === 'danger',
                         'bg-warning-light text-warning':   variant === 'warning',
                         'bg-primary-50    text-primary-600': variant === 'primary',
                         'bg-success-light text-success':   variant === 'success',
                     }">
                    {{-- Iconos pre-renderizados por Lucide — Alpine muestra el correcto --}}
                    <i data-lucide="alert-triangle" class="w-5 h-5" x-show="iconName === 'alert-triangle'"></i>
                    <i data-lucide="alert-circle"   class="w-5 h-5" x-show="iconName === 'alert-circle'"></i>
                    <i data-lucide="help-circle"    class="w-5 h-5" x-show="iconName === 'help-circle'"></i>
                    <i data-lucide="check-circle"   class="w-5 h-5" x-show="iconName === 'check-circle'"></i>
                </div>

                {{-- Título + descripción --}}
                <div class="min-w-0 flex-1 pt-0.5">
                    <h2 class="text-h3 text-text-primary leading-snug"
                        x-text="title"></h2>
                    <p class="text-small text-text-muted mt-1 leading-relaxed"
                       x-show="description"
                       x-text="description"></p>
                </div>
            </div>

            {{-- ── Acciones ── --}}
            <div class="flex items-center justify-end gap-2">

                {{-- Cancelar --}}
                <button type="button"
                        @click="close()"
                        :disabled="loading"
                        class="btn-secondary"
                        x-text="cancelLabel"></button>

                {{-- Confirmar --}}
                <button type="button"
                        @click="execute()"
                        :disabled="loading"
                        :class="confirmBtnClass"
                        class="relative min-w-[90px]">

                    {{-- Texto (se oculta durante loading) --}}
                    <span :class="loading ? 'opacity-0' : 'opacity-100'"
                          class="inline-flex items-center gap-1.5 transition-opacity"
                          x-text="confirmLabel"></span>

                    {{-- Spinner durante loading --}}
                    <span x-show="loading"
                          class="absolute inset-0 flex items-center justify-center">
                        <span class="spinner spinner-sm"></span>
                    </span>

                </button>
            </div>
        </div>
    </div>
</div>
