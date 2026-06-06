<x-modal show="isOpen" title="Configuración del Sistema" maxWidth="5xl">
    <div x-data="{ tab: @entangle('activeTab') }" class="flex flex-col md:flex-row h-[580px] max-h-[70vh] overflow-hidden">
        {{-- Sidebar Interno --}}
        <div class="w-full md:w-56 border-b md:border-b-0 md:border-r border-border bg-surface-hover/20 p-4 shrink-0 flex flex-col justify-between">
            <nav class="space-y-1">
                <button type="button" @click="tab = 'empresa'" 
                        :class="tab === 'empresa' ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-text-secondary hover:bg-surface-hover'"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-md text-body transition-all duration-150 text-left cursor-pointer">
                    <i data-lucide="building-2" class="w-4 h-4 shrink-0"></i>
                    <span>Empresa</span>
                </button>
                <button type="button" @click="tab = 'documentos'" 
                        :class="tab === 'documentos' ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-text-secondary hover:bg-surface-hover'"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-md text-body transition-all duration-150 text-left cursor-pointer">
                    <i data-lucide="file-text" class="w-4 h-4 shrink-0"></i>
                    <span>Documentos</span>
                </button>
            </nav>
        </div>

        {{-- Contenido --}}
        <div class="flex-1 p-6 overflow-y-auto h-full space-y-6">

    {{-- ══ Tab: Empresa ══ --}}
    <div x-show="tab === 'empresa'" x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

        <form wire:submit="saveEmpresa">

            {{-- Card: Identidad --}}
            <div class="mb-6">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Identidad de la empresa</h2>
                        <p class="card-subtitle">Aparece en PDFs, cotizaciones y documentos generados.</p>
                    </div>
                </div>

                {{-- Logo --}}
                <div class="section-row">
                    <div>
                        <p class="section-row-label">Logo</p>
                        <p class="section-row-desc">Formatos JPG, PNG, SVG. Máximo 1 MB.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        @if($company_logo && !$newLogo)
                            <div class="relative shrink-0" wire:loading.class="hidden" wire:target="newLogo">
                                <img src="{{ asset('storage/' . $company_logo) }}" alt="Logo"
                                     class="h-16 w-auto max-w-[10rem] object-contain border border-border rounded-lg p-1 bg-surface-card">
                                <button type="button" wire:click="deleteLogo"
                                        class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-surface-card border border-border shadow-sm flex items-center justify-center text-danger hover:bg-surface-hover transition">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <x-file-input wire:key="company-logo" inputId="company-logo-upload" wire:model="newLogo" variant="compact-inline" accept=".jpg,.jpeg,.png,.svg" maxSize="1 MB" />
                        </div>
                    </div>
                </div>

                {{-- Nombre --}}
                <div class="section-row">
                    <div>
                        <p class="section-row-label">Nombre de la empresa <span class="text-danger">*</span></p>
                        <p class="section-row-desc">Razón social completa.</p>
                    </div>
                    <x-form-field :error="$errors->first('company_name')">
                        <input type="text" wire:model="company_name" class="input" placeholder="Constructora Muulsinik S.A. de C.V.">
                    </x-form-field>
                </div>

                {{-- RFC --}}
                <div class="section-row">
                    <div>
                        <p class="section-row-label">RFC</p>
                        <p class="section-row-desc">Registro Federal de Contribuyentes.</p>
                    </div>
                    <x-form-field :error="$errors->first('company_rfc')">
                        <input type="text" wire:model="company_rfc" class="input" placeholder="ABC123456ABC1" style="font-family: ui-monospace, monospace; letter-spacing: 0.05em;">
                    </x-form-field>
                </div>

                {{-- Dirección --}}
                <div class="section-row">
                    <div>
                        <p class="section-row-label">Dirección fiscal</p>
                        <p class="section-row-desc">Calle, número, colonia, municipio, código postal.</p>
                    </div>
                    <x-form-field :error="$errors->first('company_address')">
                        <textarea wire:model="company_address" rows="2" class="input" placeholder="Calle 60 Norte #123, Col. Centro, Mérida, Yuc. C.P. 97000"></textarea>
                    </x-form-field>
                </div>

                {{-- Contacto --}}
                <div class="section-row">
                    <div>
                        <p class="section-row-label">Contacto</p>
                        <p class="section-row-desc">Teléfono y correo para documentos.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <x-form-field label="Teléfono" error="{{ $errors->first('company_phone') }}">
                            <input type="text" wire:model="company_phone" class="input" placeholder="+52 999 123 4567">
                        </x-form-field>
                        <x-form-field label="Correo electrónico" error="{{ $errors->first('company_email') }}">
                            <input type="email" wire:model="company_email" class="input" placeholder="contacto@empresa.com">
                        </x-form-field>
                    </div>
                </div>
            </div>

            {{-- Footer action --}}
            <div class="flex justify-end">
                <x-button type="submit" variant="primary" target="saveEmpresa" icon="check">Guardar cambios</x-button>
            </div>

        </form>
    </div>

    {{-- ══ Tab: Documentos ══ --}}
    <div x-show="tab === 'documentos'" x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         style="display:none;">

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
                        <input type="text" wire:model="req_prefix" class="input" placeholder="REQ-"
                               style="font-family: ui-monospace, monospace; letter-spacing: 0.05em;">
                        @if($req_prefix && $req_next_number)
                            <p class="mt-1.5 text-xs-fluid text-text-muted">
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
                        <div class="flex items-center gap-2 text-xs-fluid text-text-muted">
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

        </div>
    </div>
</x-modal>
