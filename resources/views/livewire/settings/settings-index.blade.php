<div x-data="{ tab: @entangle('activeTab') }" class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-text-primary">Configuración</h1>
            <p class="text-sm text-text-secondary">Administra la configuración general del sistema</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-border">
        <nav class="flex gap-1">
            <button @click="tab = 'empresa'"
                    :class="tab === 'empresa' ? 'border-primary-600 text-primary-600' : 'border-transparent text-text-secondary hover:text-text-primary'"
                    class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors">
                <i data-lucide="building-2" class="w-4 h-4"></i>
                <span>Empresa</span>
            </button>
            <button @click="tab = 'documentos'"
                    :class="tab === 'documentos' ? 'border-primary-600 text-primary-600' : 'border-transparent text-text-secondary hover:text-text-primary'"
                    class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Documentos</span>
            </button>
        </nav>
    </div>

    {{-- Tab: Empresa --}}
    <div x-show="tab === 'empresa'" x-transition class="space-y-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title flex items-center gap-2">
                    <i data-lucide="building-2" class="w-5 h-5 text-primary-600"></i>
                    Datos de la Empresa
                </h2>
                <p class="card-subtitle">Esta información aparecerá en los documentos generados (PDFs, cotizaciones)</p>
            </div>

            <form wire:submit="saveEmpresa" class="card-body space-y-5">
                {{-- Logo --}}
                <div>
                    <label class="input-label">Logo de la empresa</label>
                    <div class="flex items-center gap-4 mt-2">
                        @if($company_logo)
                            <div class="relative">
                                <img src="{{ Storage::url($company_logo) }}" alt="Logo" class="h-20 w-auto object-contain border rounded-lg p-2">
                                <button type="button" wire:click="deleteLogo" class="absolute -top-2 -right-2 p-1 bg-red-100 text-red-600 rounded-full hover:bg-red-200">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                        @else
                            <div class="h-20 w-32 bg-surface-card border-2 border-dashed border-border rounded-lg flex items-center justify-center text-text-muted">
                                <span class="text-sm">Sin logo</span>
                            </div>
                        @endif

                        <div class="flex-1">
                            <input type="file" wire:model="newLogo" accept="image/*" class="input-file">
                            <p class="text-xs text-text-muted mt-1">Formatos: JPG, PNG, SVG. Máx: 1MB</p>
                            @error('newLogo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Nombre --}}
                    <div>
                        <label class="input-label">Nombre de la empresa *</label>
                        <input type="text" wire:model="company_name" class="input" placeholder="Constructora Muulsinik S.A. de C.V.">
                        @error('company_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- RFC --}}
                    <div>
                        <label class="input-label">RFC</label>
                        <input type="text" wire:model="company_rfc" class="input" placeholder="ABC123456ABC1">
                        @error('company_rfc') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Dirección --}}
                    <div class="md:col-span-2">
                        <label class="input-label">Dirección fiscal</label>
                        <textarea wire:model="company_address" rows="2" class="input" placeholder="Calle, número, colonia, ciudad, código postal"></textarea>
                        @error('company_address') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Teléfono --}}
                    <div>
                        <label class="input-label">Teléfono</label>
                        <input type="text" wire:model="company_phone" class="input" placeholder="+52 999 123 4567">
                        @error('company_phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="input-label">Correo electrónico</label>
                        <input type="email" wire:model="company_email" class="input" placeholder="contacto@muulsinik.com">
                        @error('company_email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                        <i data-lucide="save" class="w-4 h-4" wire:loading.remove wire:target="saveEmpresa"></i>
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin" wire:loading wire:target="saveEmpresa"></i>
                        <span wire:loading.remove wire:target="saveEmpresa">Guardar cambios</span>
                        <span wire:loading wire:target="saveEmpresa">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Tab: Documentos --}}
    <div x-show="tab === 'documentos'" x-transition class="space-y-6" style="display: none;">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title flex items-center gap-2">
                    <i data-lucide="file-text" class="w-5 h-5 text-primary-600"></i>
                    Configuración de Documentos
                </h2>
                <p class="card-subtitle">Personaliza la numeración y formato de documentos</p>
            </div>

            <form wire:submit="saveDocumentos" class="card-body space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Prefijo Requisiciones --}}
                    <div>
                        <label class="input-label">Prefijo de requisiciones</label>
                        <input type="text" wire:model="req_prefix" class="input" placeholder="REQ-">
                        <p class="text-xs text-text-muted mt-1">Ejemplo: {{ $req_prefix }}{{ $req_next_number }}</p>
                        @error('req_prefix') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Siguiente número --}}
                    <div>
                        <label class="input-label">Siguiente número de requisición</label>
                        <input type="number" wire:model="req_next_number" class="input" min="1">
                        <p class="text-xs text-text-muted mt-1">Se incrementa automáticamente</p>
                        @error('req_next_number') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="border-t border-border pt-5">
                    <h3 class="text-sm font-semibold text-text-primary mb-4">Formato de moneda</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        {{-- Símbolo --}}
                        <div>
                            <label class="input-label">Símbolo monetario</label>
                            <input type="text" wire:model="currency_symbol" class="input" placeholder="$">
                            @error('currency_symbol') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Posición --}}
                        <div>
                            <label class="input-label">Posición del símbolo</label>
                            <select wire:model="currency_position" class="input">
                                <option value="before">Antes ($100)</option>
                                <option value="after">Después (100$)</option>
                            </select>
                            @error('currency_position') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Decimales --}}
                        <div>
                            <label class="input-label">Decimales</label>
                            <input type="number" wire:model="decimal_places" class="input" min="0" max="4">
                            @error('decimal_places') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="text-sm text-text-secondary mt-3">
                        Vista previa:
                        <span class="font-mono bg-surface-card px-2 py-1 rounded">
                            {{ $currency_position === 'before' ? $currency_symbol : '' }}1,234.{{ str_repeat('0', $decimal_places) }}{{ $currency_position === 'after' ? $currency_symbol : '' }}
                        </span>
                    </p>
                </div>

                <div class="border-t border-border pt-5">
                    <label class="input-label">Términos y condiciones (para cotizaciones)</label>
                    <textarea wire:model="terms_conditions" rows="4" class="input" placeholder="Precios sujetos a cambio sin previo aviso. Vigencia de cotización: 15 días..."></textarea>
                    <p class="text-xs text-text-muted mt-1">Aparecerá al final de las cotizaciones generadas</p>
                    @error('terms_conditions') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                        <i data-lucide="save" class="w-4 h-4" wire:loading.remove wire:target="saveDocumentos"></i>
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin" wire:loading wire:target="saveDocumentos"></i>
                        <span wire:loading.remove wire:target="saveDocumentos">Guardar cambios</span>
                        <span wire:loading wire:target="saveDocumentos">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
