<div>
    <form wire:submit="saveDocumentos">

        {{-- ══ Sección: Numeración de documentos ══ --}}
        <div class="card-header">
            <div>
                <h2 class="card-title">Numeración de documentos</h2>
                <p class="card-subtitle">Controla el prefijo y la secuencia automática de las requisiciones.</p>
            </div>
        </div>

        <div class="space-y-0">

            {{-- Prefijo --}}
            <div class="section-row">
                <div>
                    <p class="section-row-label">Prefijo de requisiciones</p>
                    <p class="section-row-desc">
                        Texto fijo que antecede al número de folio.
                        Ejemplo: <span class="font-mono text-text-primary">REQ-</span>
                    </p>
                </div>
                <x-form-field :error="$errors->first('req_prefix')">
                    <input type="text"
                           id="req_prefix"
                           wire:model="req_prefix"
                           class="input font-mono tracking-wider"
                           placeholder="REQ-"
                           maxlength="10"
                           autocomplete="off">
                    @if($req_prefix && $req_next_number)
                        <div class="mt-2 flex items-center gap-2 text-xs text-text-muted">
                            <span>Próximo folio estimado:</span>
                            <span class="font-mono bg-surface-main px-2.5 py-1 rounded-md border border-border text-text-primary font-medium text-xs">
                                {{ $req_prefix }}{{ str_pad($req_next_number, 4, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>
                    @endif
                </x-form-field>
            </div>

            {{-- Siguiente número --}}
            <div class="section-row">
                <div>
                    <p class="section-row-label">Siguiente número de folio</p>
                    <p class="section-row-desc">Se incrementa automáticamente cada vez que se crea una requisición.</p>
                </div>
                <x-form-field :error="$errors->first('req_next_number')">
                    <input type="number"
                           id="req_next_number"
                           wire:model="req_next_number"
                           class="input"
                           min="1"
                           placeholder="1"
                           autocomplete="off">
                </x-form-field>
            </div>

        </div>

        {{-- ══ Sección: Formato de moneda ══ --}}
        <div class="card-header pt-6 mt-6 border-t border-border">
            <div>
                <h2 class="card-title">Formato de moneda</h2>
                <p class="card-subtitle">Símbolo y precisión numérica para todos los importes del sistema.</p>
            </div>
        </div>

        <div class="space-y-0">

            {{-- Símbolo + posición + decimales — stacked por ser grupo de 3 inputs relacionados --}}
            <div class="section-row-stacked">
                <div>
                    <p class="section-row-label">Configuración del símbolo monetario</p>
                    <p class="section-row-desc">Define cómo se imprimen los importes en reportes, requisiciones y cotizaciones.</p>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <x-form-field label="Símbolo" :error="$errors->first('currency_symbol')">
                            <input type="text"
                                   id="currency_symbol"
                                   wire:model.live="currency_symbol"
                                   class="input"
                                   placeholder="$"
                                   maxlength="5">
                        </x-form-field>
                        <x-form-field label="Posición del símbolo" :error="$errors->first('currency_position')">
                            <x-custom-select
                                wire:model.live="currency_position"
                                :options="['before' => 'Antes  ($100)', 'after' => 'Después (100$)']"
                                placeholder="" />
                        </x-form-field>
                        <x-form-field label="Decimales" :error="$errors->first('decimal_places')">
                            <input type="number"
                                   id="decimal_places"
                                   wire:model.live="decimal_places"
                                   class="input"
                                   min="0"
                                   max="4"
                                   placeholder="2">
                        </x-form-field>
                    </div>
                    {{-- Vista previa reactiva estandarizada --}}
                    <div class="flex items-center gap-2 text-xs text-text-muted">
                        <span>Vista previa en documento:</span>
                        <span class="font-mono bg-surface-main px-2.5 py-1 rounded-md border border-border text-text-primary font-medium text-xs">
                            {{ $currency_position === 'before' ? $currency_symbol : '' }}1,234.{{ str_repeat('0', max(0, (int)$decimal_places)) }}{{ $currency_position === 'after' ? $currency_symbol : '' }}
                        </span>
                    </div>
                </div>
            </div>

        </div>

        {{-- ══ Sección: Términos y condiciones ══ --}}
        <div class="card-header pt-6 mt-6 border-t border-border">
            <div>
                <h2 class="card-title">Términos y condiciones</h2>
                <p class="card-subtitle">Texto legal que se imprime al pie de cada cotización generada.</p>
            </div>
        </div>

        <div class="space-y-0">

            {{-- Textarea — stacked por ser contenido extenso --}}
            <div class="section-row-stacked">
                <p class="section-row-desc">Puede incluir vigencia de precios, condiciones de pago, garantías, y cualquier cláusula comercial relevante.</p>
                <x-form-field :error="$errors->first('terms_conditions')">
                    <textarea id="terms_conditions"
                              wire:model="terms_conditions"
                              rows="6"
                              class="input"
                              placeholder="Precios sujetos a cambio sin previo aviso. Vigencia de cotización: 15 días naturales. Entrega sujeta a disponibilidad de inventario."></textarea>
                </x-form-field>
            </div>

        </div>

        {{-- Footer: botón de acción --}}
        @if(auth()->user()?->hasPermission('configuracion.editar') || auth()->user()?->hasPermission('*'))
            <div class="flex justify-end pt-5 mt-5 border-t border-border">
                <x-button type="submit" variant="primary" target="saveDocumentos" icon="check">
                    Guardar cambios
                </x-button>
            </div>
        @endif

    </form>
</div>
