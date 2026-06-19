<div>
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
                                <x-lucide-trash-2 class="w-3.5 h-3.5" />
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
                    <p class="section-row-label">Nombre de la empresa <span class="text-danger font-medium ml-0.5" aria-hidden="true">*</span></p>
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
                    <input type="text" wire:model="company_rfc" class="input font-mono tracking-wider" placeholder="ABC123456ABC1">
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
