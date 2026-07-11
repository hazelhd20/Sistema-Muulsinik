<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
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
            'company_name' => 'required|string|max:150',
            'company_rfc' => 'nullable|string|max:13',
            'company_address' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:20',
            'company_email' => 'nullable|email|max:100',
            'newLogo' => 'nullable|image|max:1024',
        ]);

        $disk = config('filesystems.default');
        if ($this->remove_logo && ! $this->newLogo) {
            \App\Support\StorageResolver::delete($this->company_logo);
            Setting::set('company_logo', null, 'string');
            $this->company_logo = null;
            $this->remove_logo = false;
        } elseif ($this->newLogo) {
            \App\Support\StorageResolver::delete($this->company_logo);
            $path = $this->newLogo->store('company', $disk);
            $this->company_logo = $path;
            Setting::set('company_logo', $path, 'string');
            $this->newLogo = null;
            $this->remove_logo = false;
        }

        Setting::set('company_name', $this->company_name, 'string');
        Setting::set('company_rfc', $this->company_rfc, 'string');
        Setting::set('company_address', $this->company_address, 'string');
        Setting::set('company_phone', $this->company_phone, 'string');
        Setting::set('company_email', $this->company_email, 'string');

        Setting::clearCache();

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

    public function render()
    {
        return view('livewire.settings.settings-company');
    }
}
