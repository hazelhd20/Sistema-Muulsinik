<div x-data="{ tab: @entangle('activeTab') }">

    {{-- Header --}}
    <x-page-header subtitle="Sistema" title="Configuración" />

    {{-- Tabs --}}
    <nav class="tab-nav">
        <button @click="tab = 'empresa'" :class="tab === 'empresa' ? 'active' : ''" class="tab-btn">
            <i data-lucide="building-2"></i>
            Empresa
        </button>
        <button @click="tab = 'documentos'" :class="tab === 'documentos' ? 'active' : ''" class="tab-btn">
            <i data-lucide="file-text"></i>
            Documentos
        </button>
    </nav>

    {{-- ══ Tab: Empresa ══ --}}
    <div x-show="tab === 'empresa'" x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

        <form wire:submit="saveEmpresa">

            {{-- Card: Identidad --}}
            <div class="card mb-4">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">
                            <i data-lucide="building-2" class="w-4 h-4"></i>
                            Identidad de la empresa
                        </h2>
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
                        @if($company_logo)
                            <div class="relative shrink-0">
                                <img src="{{ Storage::url($company_logo) }}" alt="Logo"
                                     class="h-16 w-auto object-contain border border-border rounded-lg p-2 bg-surface-main">
                                <button type="button" wire:click="deleteLogo"
                                        class="absolute -top-2 -right-2 icon-wrap-sm bg-white border border-border shadow-sm text-danger hover:bg-red-50 transition-colors">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        @else
                            <div class="shrink-0 h-16 w-28 bg-surface-main border-2 border-dashed border-border rounded-lg flex flex-col items-center justify-center gap-1 text-text-muted">
                                <i data-lucide="image" class="w-5 h-5 opacity-30"></i>
                                <span class="text-xs-fluid">Sin logo</span>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <input type="file" wire:model="newLogo" accept="image/*" class="input text-small">
                            @error('newLogo') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Nombre --}}
                <div class="section-row">
                    <div>
                        <p class="section-row-label">Nombre de la empresa <span class="text-danger">*</span></p>
                        <p class="section-row-desc">Razón social completa.</p>
                    </div>
                    <div>
                        <input type="text" wire:model="company_name" class="input" placeholder="Constructora Muulsinik S.A. de C.V.">
                        @error('company_name') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- RFC --}}
                <div class="section-row">
                    <div>
                        <p class="section-row-label">RFC</p>
                        <p class="section-row-desc">Registro Federal de Contribuyentes.</p>
                    </div>
                    <div>
                        <input type="text" wire:model="company_rfc" class="input" placeholder="ABC123456ABC1" style="font-family: ui-monospace, monospace; letter-spacing: 0.05em;">
                        @error('company_rfc') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Dirección --}}
                <div class="section-row">
                    <div>
                        <p class="section-row-label">Dirección fiscal</p>
                        <p class="section-row-desc">Calle, número, colonia, municipio, código postal.</p>
                    </div>
                    <div>
                        <textarea wire:model="company_address" rows="2" class="input" placeholder="Calle 60 Norte #123, Col. Centro, Mérida, Yuc. C.P. 97000"></textarea>
                        @error('company_address') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Contacto --}}
                <div class="section-row">
                    <div>
                        <p class="section-row-label">Contacto</p>
                        <p class="section-row-desc">Teléfono y correo para documentos.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Teléfono</label>
                            <input type="text" wire:model="company_phone" class="input" placeholder="+52 999 123 4567">
                            @error('company_phone') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Correo electrónico</label>
                            <input type="email" wire:model="company_email" class="input" placeholder="contacto@empresa.com">
                            @error('company_email') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer action --}}
            <div class="flex justify-end">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveEmpresa" class="inline-flex items-center gap-1.5">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        Guardar cambios
                    </span>
                    <span wire:loading wire:target="saveEmpresa" class="inline-flex items-center gap-2">
                        <span class="spinner spinner-sm opacity-80"></span>
                        Guardando…
                    </span>
                </button>
            </div>

        </form>
    </div>

    {{-- ══ Tab: Documentos ══ --}}
    <div x-show="tab === 'documentos'" x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         style="display:none;">

        <form wire:submit="saveDocumentos">

            {{-- Card: Numeración --}}
            <div class="card mb-4">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">
                            <i data-lucide="hash" class="w-4 h-4"></i>
                            Numeración de documentos
                        </h2>
                        <p class="card-subtitle">Controla el prefijo y secuencia de requisiciones.</p>
                    </div>
                </div>

                {{-- Prefijo --}}
                <div class="section-row">
                    <div>
                        <p class="section-row-label">Prefijo de requisiciones</p>
                        <p class="section-row-desc">Texto fijo que precede al número. Ej: <span class="font-mono text-text-primary">REQ-</span></p>
                    </div>
                    <div>
                        <input type="text" wire:model="req_prefix" class="input" placeholder="REQ-"
                               style="font-family: ui-monospace, monospace; letter-spacing: 0.05em;">
                        @if($req_prefix && $req_next_number)
                            <p class="mt-1.5 text-xs-fluid text-text-muted">
                                Próximo número:
                                <span class="font-mono font-medium text-text-primary">{{ $req_prefix }}{{ str_pad($req_next_number, 4, '0', STR_PAD_LEFT) }}</span>
                            </p>
                        @endif
                        @error('req_prefix') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Siguiente número --}}
                <div class="section-row">
                    <div>
                        <p class="section-row-label">Siguiente número</p>
                        <p class="section-row-desc">Se incrementa automáticamente con cada requisición creada.</p>
                    </div>
                    <div>
                        <input type="number" wire:model="req_next_number" class="input" min="1">
                        @error('req_next_number') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Card: Moneda --}}
            <div class="card mb-4">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">
                            <i data-lucide="coins" class="w-4 h-4"></i>
                            Formato de moneda
                        </h2>
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
                            <div>
                                <label class="label">Símbolo</label>
                                <input type="text" wire:model.live="currency_symbol" class="input" placeholder="$">
                                @error('currency_symbol') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label">Posición</label>
                                <select wire:model.live="currency_position" class="input">
                                    <option value="before">Antes ($100)</option>
                                    <option value="after">Después (100$)</option>
                                </select>
                                @error('currency_position') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label">Decimales</label>
                                <input type="number" wire:model.live="decimal_places" class="input" min="0" max="4">
                                @error('decimal_places') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                            </div>
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
            <div class="card mb-4">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">
                            <i data-lucide="scroll-text" class="w-4 h-4"></i>
                            Términos y condiciones
                        </h2>
                        <p class="card-subtitle">Texto que aparece al pie de las cotizaciones generadas.</p>
                    </div>
                </div>

                <div class="section-row">
                    <div>
                        <p class="section-row-label">Contenido</p>
                        <p class="section-row-desc">Puede incluir vigencia, condiciones de pago, garantías, etc.</p>
                    </div>
                    <div>
                        <textarea wire:model="terms_conditions" rows="5" class="input"
                                  placeholder="Precios sujetos a cambio sin previo aviso. Vigencia de cotización: 15 días naturales…"></textarea>
                        @error('terms_conditions') <p class="mt-1 text-xs-fluid text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Footer action --}}
            <div class="flex justify-end">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveDocumentos" class="inline-flex items-center gap-1.5">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        Guardar cambios
                    </span>
                    <span wire:loading wire:target="saveDocumentos" class="inline-flex items-center gap-2">
                        <span class="spinner spinner-sm opacity-80"></span>
                        Guardando…
                    </span>
                </button>
            </div>

        </form>
    </div>

</div>
