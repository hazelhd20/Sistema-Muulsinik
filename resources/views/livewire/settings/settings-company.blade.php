<div>
    <form wire:submit="saveEmpresa">

        {{-- ══ Sección: Identidad Visual ══ --}}
        <div class="card-header">
            <div>
                <h2 class="card-title">Identidad de la empresa</h2>
                <p class="card-subtitle">Datos que aparecen en PDFs, cotizaciones y documentos generados.</p>
            </div>
        </div>

        <div class="space-y-0">

            {{-- Logo --}}
            <div class="section-row-stacked">
                <div class="mb-3">
                    <p class="section-row-label">Logo corporativo</p>
                    <p class="section-row-desc">Formatos JPG, PNG o SVG. Tamaño máximo 1 MB.</p>
                </div>
                <div class="flex items-center gap-6">
                    @if($company_logo && !$newLogo && !$remove_logo)
                        <div class="relative shrink-0" wire:loading.class="hidden" wire:target="newLogo">
                            <img src="{{ asset('storage/' . $company_logo) }}" alt="Logo actual de la empresa"
                                 class="h-16 w-auto max-w-[10rem] object-contain border border-border rounded-lg p-1.5 bg-surface-card shadow-sm">
                            @if(auth()->user()?->hasPermission('configuracion.editar') || auth()->user()?->hasPermission('*'))
                                <button type="button" wire:click="deleteLogo"
                                        class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-surface-card border border-border shadow-sm flex items-center justify-center text-danger hover:bg-danger-light transition-colors"
                                        title="Eliminar logo">
                                    <x-lucide-trash-2 class="w-3.5 h-3.5" />
                                </button>
                            @endif
                        </div>
                    @endif
                    @if($remove_logo && !$newLogo)
                        <div class="h-16 px-3.5 bg-danger-light border border-danger-border rounded-lg flex items-center gap-2.5 text-xs-fluid text-danger shrink-0">
                            <x-lucide-trash-2 class="w-4 h-4 shrink-0" />
                            <span class="font-medium">Por eliminar</span>
                            <button type="button" wire:click="$set('remove_logo', false)"
                                    class="px-2 py-1 bg-surface-card hover:bg-surface-hover text-text-primary border border-border rounded font-medium transition text-xs-fluid shadow-2xs ml-1">
                                Deshacer
                            </button>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <x-file-input wire:key="company-logo"
                                      inputId="company-logo-upload"
                                      wire:model="newLogo"
                                      variant="compact-inline"
                                      accept=".jpg,.jpeg,.png,.svg"
                                      maxSize="1 MB" />
                    </div>
                </div>
            </div>

            {{-- Nombre de la empresa --}}
            <div class="section-row">
                <div>
                    <p class="section-row-label">
                        Nombre de la empresa
                        <span class="text-danger font-medium ml-0.5" aria-hidden="true">*</span>
                    </p>
                    <p class="section-row-desc">Razón social completa tal como aparece en el RFC.</p>
                </div>
                <x-form-field :error="$errors->first('company_name')">
                    <input type="text"
                           id="company_name"
                           wire:model="company_name"
                           class="input"
                           placeholder="Constructora Muulsinik S.A. de C.V."
                           autocomplete="organization">
                </x-form-field>
            </div>

            {{-- RFC --}}
            <div class="section-row">
                <div>
                    <p class="section-row-label">RFC</p>
                    <p class="section-row-desc">Registro Federal de Contribuyentes (12 o 13 caracteres).</p>
                </div>
                <x-form-field :error="$errors->first('company_rfc')">
                    <input type="text"
                           id="company_rfc"
                           wire:model="company_rfc"
                           class="input font-mono tracking-wider"
                           placeholder="MUU840715ABC"
                           maxlength="13"
                           autocomplete="off">
                </x-form-field>
            </div>

            {{-- Dirección fiscal — stacked por textarea amplio --}}
            <div class="section-row-stacked">
                <div>
                    <p class="section-row-label">Dirección fiscal</p>
                    <p class="section-row-desc">Calle, número exterior/interior, colonia, municipio, estado y código postal.</p>
                </div>
                <x-form-field :error="$errors->first('company_address')">
                    <textarea id="company_address"
                              wire:model="company_address"
                              rows="3"
                              class="input"
                              placeholder="Calle 60 Norte #123, Col. Centro, Mérida, Yuc. C.P. 97000"></textarea>
                </x-form-field>
            </div>

            {{-- Teléfono --}}
            <div class="section-row">
                <div>
                    <p class="section-row-label">Teléfono de contacto</p>
                    <p class="section-row-desc">Número principal para atención en órdenes y cotizaciones.</p>
                </div>
                <x-form-field :error="$errors->first('company_phone')">
                    <input type="tel"
                           id="company_phone"
                           wire:model="company_phone"
                           class="input"
                           placeholder="+52 999 123 4567"
                           autocomplete="tel">
                </x-form-field>
            </div>

            {{-- Correo electrónico --}}
            <div class="section-row">
                <div>
                    <p class="section-row-label">Correo electrónico</p>
                    <p class="section-row-desc">Email oficial para el envío y recepción de documentos.</p>
                </div>
                <x-form-field :error="$errors->first('company_email')">
                    <input type="email"
                           id="company_email"
                           wire:model="company_email"
                           class="input"
                           placeholder="contacto@empresa.com"
                           autocomplete="email">
                </x-form-field>
            </div>

        </div>

        {{-- Footer: botón de acción --}}
        @if(auth()->user()?->hasPermission('configuracion.editar') || auth()->user()?->hasPermission('*'))
            <div class="flex justify-end pt-5 mt-5 border-t border-border">
                <x-button type="submit" variant="primary" target="saveEmpresa" icon="check">
                    Guardar cambios
                </x-button>
            </div>
        @endif

    </form>
</div>
