{{--
    Partial: _reject-modal
    Uso: @include('livewire.requisitions._reject-modal')
    Requiere en el componente Livewire:
        public bool $showRejectModal = false;
        public bool $isBulkReject = false;  (solo en RequisitionIndex)
        public string $rejectionComment = '';
        public function confirmReject(): void { ... }
--}}
@if($showRejectModal)
    <x-modal show="showRejectModal"
        :title="isset($isBulkReject) && $isBulkReject ? 'Rechazar Requisiciones Seleccionadas' : 'Rechazar Requisición'"
        :subtitle="isset($isBulkReject) && $isBulkReject
            ? 'Indica el motivo del rechazo para todas las seleccionadas (obligatorio)'
            : 'Indica el motivo del rechazo (obligatorio)'"
        maxWidth="md">
        <form wire:submit="confirmReject" class="p-4 sm:p-6 space-y-4">
            <x-form-field label="Motivo del rechazo" :required="true" :error="$errors->first('rejectionComment')">
                <textarea wire:model="rejectionComment"
                    class="input"
                    rows="3"
                    placeholder="Explica por qué esta requisición fue rechazada..."
                    aria-required="true"></textarea>
            </x-form-field>
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4 border-t border-border">
                <x-button wire:click="$set('showRejectModal', false)" variant="soft">
                    Cancelar
                </x-button>
                <x-button type="submit" variant="danger" icon="x-circle" target="confirmReject">
                    Confirmar Rechazo
                </x-button>
            </div>
        </form>
    </x-modal>
@endif
