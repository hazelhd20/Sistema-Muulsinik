<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsCompany extends Component
{
    use WithFileUploads;

    public string $company_name = '';
    public string $company_rfc = '';
    public string $company_address = '';
    public string $company_phone = '';
    public string $company_email = '';
    public ?string $company_logo = null;
    public $newLogo = null;
    public bool $remove_logo = false;

    public function mount(): void
    {
        $this->company_name = Setting::get('company_name', 'Constructora Muulsinik');
        $this->company_rfc = Setting::get('company_rfc', '');
        $this->company_address = Setting::get('company_address', '');
        $this->company_phone = Setting::get('company_phone', '');
        $this->company_email = Setting::get('company_email', '');
        $this->company_logo = Setting::get('company_logo');
    }

    public function updatedNewLogo(): void
    {
        if ($this->newLogo) {
            $this->remove_logo = false;
        }
    }

    public function saveEmpresa(): void
    {
        if (! auth()->user()?->hasPermission('configuracion.editar') && ! auth()->user()?->hasPermission('*')) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No tienes permiso para modificar la configuración.']);
            return;
        }

        $this->validate([
            'company_name' => 'required|string|max:255',
            'company_rfc' => 'nullable|string|max:20',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'newLogo' => 'nullable|image|max:2048', // max 2MB
        ]);

        if ($this->remove_logo && $this->company_logo) {
            \App\Support\StorageResolver::delete($this->company_logo);
            Setting::set('company_logo', null);
            $this->company_logo = null;
            $this->remove_logo = false;
        }

        if ($this->newLogo) {
            if ($this->company_logo) {
                \App\Support\StorageResolver::delete($this->company_logo);
            }
            // Guardamos directamente con path relativo ("company") para compatibilidad multi-disco S3/local
            $path = $this->newLogo->store('company', 'public');
            Setting::set('company_logo', $path);
            $this->company_logo = $path;
            $this->newLogo = null;
        }

        Setting::set('company_name', $this->company_name);
        Setting::set('company_rfc', $this->company_rfc);
        Setting::set('company_address', $this->company_address);
        Setting::set('company_phone', $this->company_phone);
        Setting::set('company_email', $this->company_email);

        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Datos de empresa guardados correctamente.']);
    }

    public function deleteLogo(): void
    {
        if (! auth()->user()?->hasPermission('configuracion.editar') && ! auth()->user()?->hasPermission('*')) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No tienes permiso para modificar la configuración.']);
            return;
        }

        $this->remove_logo = true;
        $this->newLogo = null;
    }

    /**
     * Resuelve la URL del logo sin round-trip PHP: S3 pre-signed URL o URL pública local.
     */
    #[Computed]
    public function companyLogoUrl(): ?string
    {
        if (! $this->company_logo) {
            return null;
        }

        return \App\Support\StorageResolver::resolveUrl($this->company_logo)
            ?? route('file.preview', ['path' => $this->company_logo]);
    }

    public function render()
    {
        return view('livewire.settings.settings-company');
    }
}
