<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsIndex extends Component
{
    use WithFileUploads;

    // Tab activa
    public string $activeTab = 'empresa';

    // Datos de Empresa
    public string $company_name = '';
    public string $company_rfc = '';
    public string $company_address = '';
    public string $company_phone = '';
    public string $company_email = '';
    public ?string $company_logo = null;
    public $newLogo = null;

    // Configuración de Documentos
    public string $req_prefix = 'REQ-';
    public int $req_next_number = 1;
    public string $currency_symbol = '$';
    public string $currency_position = 'before';
    public int $decimal_places = 2;
    public string $terms_conditions = '';


    protected function rules(): array
    {
        return [
            'company_name' => 'required|string|max:150',
            'company_rfc' => 'nullable|string|max:13',
            'company_address' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:20',
            'company_email' => 'nullable|email|max:100',
            'newLogo' => 'nullable|image|max:1024',
            'req_prefix' => 'required|string|max:10',
            'req_next_number' => 'required|integer|min:1',
            'currency_symbol' => 'required|string|max:5',
            'currency_position' => 'required|in:before,after',
            'decimal_places' => 'required|integer|min:0|max:4',
            'terms_conditions' => 'nullable|string',
        ];
    }

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        // Cargar configuraciones de Empresa
        $this->company_name = Setting::get('company_name', 'Constructora Muulsinik');
        $this->company_rfc = Setting::get('company_rfc', '');
        $this->company_address = Setting::get('company_address', '');
        $this->company_phone = Setting::get('company_phone', '');
        $this->company_email = Setting::get('company_email', '');
        $this->company_logo = Setting::get('company_logo');

        // Cargar configuración de Documentos
        $this->req_prefix = Setting::get('req_prefix', 'REQ-');
        $this->req_next_number = Setting::get('req_next_number', 1);
        $this->currency_symbol = Setting::get('currency_symbol', '$');
        $this->currency_position = Setting::get('currency_position', 'before');
        $this->decimal_places = Setting::get('decimal_places', 2);
        $this->terms_conditions = Setting::get('terms_conditions', '');

    }

    public function saveEmpresa(): void
    {
        $this->validate([
            'company_name' => 'required|string|max:150',
            'company_rfc' => 'nullable|string|max:13',
            'company_address' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:20',
            'company_email' => 'nullable|email|max:100',
            'newLogo' => 'nullable|image|max:1024',
        ]);

        // Procesar logo si se subió uno nuevo
        if ($this->newLogo) {
            $path = $this->newLogo->store('company', 'public');
            $this->company_logo = $path;
            Setting::set('company_logo', $path, 'string');
            $this->newLogo = null;
        }

        Setting::set('company_name', $this->company_name, 'string');
        Setting::set('company_rfc', $this->company_rfc, 'string');
        Setting::set('company_address', $this->company_address, 'string');
        Setting::set('company_phone', $this->company_phone, 'string');
        Setting::set('company_email', $this->company_email, 'string');

        Setting::clearCache();

        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Datos de empresa guardados correctamente.']);
    }

    public function saveDocumentos(): void
    {
        $this->validate([
            'req_prefix' => 'required|string|max:10',
            'req_next_number' => 'required|integer|min:1',
            'currency_symbol' => 'required|string|max:5',
            'currency_position' => 'required|in:before,after',
            'decimal_places' => 'required|integer|min:0|max:4',
            'terms_conditions' => 'nullable|string',
        ]);

        Setting::set('req_prefix', $this->req_prefix, 'string');
        Setting::set('req_next_number', $this->req_next_number, 'number');
        Setting::set('currency_symbol', $this->currency_symbol, 'string');
        Setting::set('currency_position', $this->currency_position, 'string');
        Setting::set('decimal_places', $this->decimal_places, 'number');
        Setting::set('terms_conditions', $this->terms_conditions, 'string');

        Setting::clearCache();

        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Configuración de documentos guardada.']);
    }

    public function deleteLogo(): void
    {
        if ($this->company_logo && Storage::disk('public')->exists($this->company_logo)) {
            Storage::disk('public')->delete($this->company_logo);
        }

        Setting::set('company_logo', null, 'string');
        $this->company_logo = null;
        Setting::clearCache();

        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Logo eliminado.']);
    }

    public function render()
    {
        return view('livewire.settings.settings-index')
            ->layout('components.layouts.app', ['title' => 'Configuración']);
    }
}
