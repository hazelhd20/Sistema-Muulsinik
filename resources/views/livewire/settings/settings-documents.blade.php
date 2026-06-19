<div>
    <form wire:submit="saveDocumentos">
        {{-- Card: Numeración --}}
        <div class="mb-6">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Numeración de documentos</h2>
                    <p class="card-subtitle">Controla el prefijo y secuencia de requisiciones.</p>
                </div>
            </div>

            {{-- Prefijo --}}
            <div class="section-row">
                <div>
                    <p class="section-row-label">Prefijo de requisiciones</p>
                    <p class="section-row-desc">Texto fijo que precede al número. Ej: <span class="font-mono text-text-primary">REQ-</span></p>
                </div>
                <x-form-field :error="$errors->first('req_prefix')">
                    <input type="text" wire:model="req_prefix" class="input font-mono tracking-wider" placeholder="REQ-">
                    @if($req_prefix && $req_next_number)
                        <p class="mt-1.5 text-xs text-text-muted">
                            Próximo número:
                            <span class="font-mono font-medium text-text-primary">{{ $req_prefix }}{{ str_pad($req_next_number, 4, '0', STR_PAD_LEFT) }}</span>
                        </p>
                    @endif
                </x-form-field>
            </div>

            {{-- Siguiente número --}}
            <div class="section-row">
                <div>
                    <p class="section-row-label">Siguiente número</p>
                    <p class="section-row-desc">Se incrementa automáticamente con cada requisición creada.</p>
                </div>
                <x-form-field :error="$errors->first('req_next_number')">
                    <input type="number" wire:model="req_next_number" class="input" min="1">
                </x-form-field>
            </div>
        </div>

        {{-- Card: Moneda --}}
        <div class="mb-6">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Formato de moneda</h2>
                    <p class="card-subtitle">Símbolo y formato numérico para importes.</p>
                </div>
            </div>

            {{-- Símbolo + posición + decimales --}}
            <div class="section-row">
                <div>
                    <p class="section-row-label">Símbolo y posición</p>
                    <p class="section-row-desc">Define cómo se muestran los montos en documentos.</p>
                </div>
                <div class="space-y-3">
                    <div class="grid grid-cols-3 gap-3">
                        <x-form-field label="Símbolo" error="{{ $errors->first('currency_symbol') }}">
                            <input type="text" wire:model.live="currency_symbol" class="input" placeholder="$">
                        </x-form-field>
                        <x-form-field label="Posición" error="{{ $errors->first('currency_position') }}">
                            <x-custom-select wire:model.live="currency_position" :options="['before' => 'Antes ($100)', 'after' => 'Después (100$)']" placeholder="" />
                        </x-form-field>
                        <x-form-field label="Decimales" error="{{ $errors->first('decimal_places') }}">
                            <input type="number" wire:model.live="decimal_places" class="input" min="0" max="4">
                        </x-form-field>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-text-muted">
                        <span>Vista previa:</span>
                        <span class="font-mono bg-surface-main px-2.5 py-1 rounded-md border border-border text-text-primary font-medium">
                            {{ $currency_position === 'before' ? $currency_symbol : '' }}1,234.{{ str_repeat('0', max(0, (int)$decimal_places)) }}{{ $currency_position === 'after' ? $currency_symbol : '' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card: Términos --}}
        <div class="mb-6">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Términos y condiciones</h2>
                    <p class="card-subtitle">Texto que aparece al pie de las cotizaciones generadas.</p>
                </div>
            </div>

            <div class="section-row">
                <div>
                    <p class="section-row-label">Contenido</p>
                    <p class="section-row-desc">Puede incluir vigencia, condiciones de pago, garantías, etc.</p>
                </div>
                <x-form-field :error="$errors->first('terms_conditions')">
                    <textarea wire:model="terms_conditions" rows="5" class="input"
                              placeholder="Precios sujetos a cambio sin previo aviso. Vigencia de cotización: 15 días naturales…"></textarea>
                </x-form-field>
            </div>
        </div>

        {{-- Footer action --}}
        <div class="flex justify-end">
            <x-button type="submit" variant="primary" target="saveDocumentos" icon="check">Guardar cambios</x-button>
        </div>
    </form>
</div>
