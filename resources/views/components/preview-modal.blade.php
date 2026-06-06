<div x-show="showPreviewModal" x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    @keydown.escape.window="showPreviewModal = false">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showPreviewModal = false"></div>
    <div class="modal-card w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden animate-scale-in"
        x-transition>
        <div class="px-5 py-4 border-b border-border flex items-center justify-between bg-surface-card">
            <h3 class="text-h2 text-text-primary flex items-center gap-2">
                <i data-lucide="file-search" class="w-5 h-5 text-primary-600"></i> Vista Previa del Documento
            </h3>
            <button @click="showPreviewModal = false"
                class="p-1.5 rounded-lg hover:bg-surface-hover text-text-muted transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="flex-1 overflow-hidden bg-surface-main p-4 relative">
            <template x-if="isImage()">
                <img :src="previewUrl" class="w-full h-full object-contain rounded-lg">
            </template>
            <template x-if="isPdf()">
                <iframe :src="previewUrl"
                    class="w-full h-full border border-border rounded-lg shadow-sm bg-surface-card"></iframe>
            </template>
            <template x-if="!isImage() && !isPdf()">
                <div class="flex flex-col items-center justify-center h-full text-text-muted gap-3">
                    <i data-lucide="file-question" class="w-12 h-12 opacity-50"></i>
                    <p class="font-medium text-body">Vista previa no disponible para este tipo de archivo.</p>
                    <x-button x-bind:href="previewUrl" target="_blank" variant="secondary" icon="download" class="mt-2 text-small">
                        Descargar
                    </x-button>
                </div>
            </template>
        </div>
    </div>
</div>
