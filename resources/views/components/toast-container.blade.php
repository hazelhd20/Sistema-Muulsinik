<div x-data="{
        toasts: [],
        add(event) {
            const data = Array.isArray(event.detail) ? event.detail[0] : event.detail;
            const toast = {
                id: Date.now() + Math.random(),
                type: data.icon || 'success', // success, danger, error, warning, info
                message: data.title || data.message,
                show: true,
                duration: data.duration !== undefined ? data.duration : 3000
            };
            this.toasts.push(toast);
            if (toast.duration > 0) {
                setTimeout(() => this.remove(toast.id), toast.duration);
            }
        },
        remove(id) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index >= 0) {
                this.toasts[index].show = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 300); // match transition duration
            }
        }
    }"
    @toast.window="add($event)"
    class="fixed top-4 right-4 z-[100] flex flex-col gap-3 pointer-events-none w-full max-w-sm"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-full"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-4"
             class="pointer-events-auto bg-surface-card border border-border shadow-lg rounded-xl p-4 flex items-center gap-3 relative overflow-hidden"
        >
            <!-- Icon con fondo suave estandarizado -->
            <div class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
                 :class="{
                    'bg-success-light text-success': toast.type === 'success',
                    'bg-danger-light text-danger': toast.type === 'danger' || toast.type === 'error',
                    'bg-warning-light text-warning': toast.type === 'warning',
                    'bg-info-light text-info': toast.type === 'info' || toast.type === 'question' || !['success', 'danger', 'error', 'warning'].includes(toast.type)
                 }">
                <template x-if="toast.type === 'success'">
                    <x-lucide-check-circle class="w-4 h-4" />
                </template>
                <template x-if="toast.type === 'danger' || toast.type === 'error'">
                    <x-lucide-alert-circle class="w-4 h-4" />
                </template>
                <template x-if="toast.type === 'warning'">
                    <x-lucide-alert-triangle class="w-4 h-4" />
                </template>
                <template x-if="toast.type === 'info' || toast.type === 'question' || !['success', 'danger', 'error', 'warning'].includes(toast.type)">
                    <x-lucide-info class="w-4 h-4" />
                </template>
            </div>
            
            <!-- Content -->
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-text-primary leading-tight" x-text="toast.message"></p>
            </div>
            
            <!-- Close Button -->
            <button @click="remove(toast.id)" class="btn-close -mr-1">
                <x-lucide-x class="w-4 h-4" />
            </button>
            
            <!-- Progress Bar -->
            <template x-if="toast.duration > 0">
                <div class="absolute bottom-0 left-0 h-1 bg-border/50 w-full">
                    <div class="h-full origin-left"
                         :style="`animation: toast-progress ${toast.duration}ms linear forwards;`"
                         :class="{
                            'bg-success': toast.type === 'success',
                            'bg-danger': toast.type === 'danger' || toast.type === 'error',
                            'bg-warning': toast.type === 'warning',
                            'bg-info': toast.type === 'info' || toast.type === 'question' || !['success', 'danger', 'error', 'warning'].includes(toast.type)
                         }"
                    ></div>
                </div>
            </template>
        </div>
    </template>

    <style>
        @keyframes toast-progress {
            0% { transform: scaleX(1); }
            100% { transform: scaleX(0); }
        }
    </style>
</div>
