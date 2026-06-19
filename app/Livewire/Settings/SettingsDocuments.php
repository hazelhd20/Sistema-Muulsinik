<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Livewire\Component;

class SettingsDocuments extends Component
{
    public string $req_prefix = 'REQ-';
    public int $req_next_number = 1;
    public string $currency_symbol = '$';
    public string $currency_position = 'before';
    public int $decimal_places = 2;
    public string $terms_conditions = '';

    public function mount(): void
    {
        $this->req_prefix = Setting::get('req_prefix', 'REQ-');
        $this->req_next_number = Setting::get('req_next_number', 1);
        $this->currency_symbol = Setting::get('currency_symbol', '$');
        $this->currency_position = Setting::get('currency_position', 'before');
        $this->decimal_places = Setting::get('decimal_places', 2);
        $this->terms_conditions = Setting::get('terms_conditions', '');
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

    public function render()
    {
        return view('livewire.settings.settings-documents');
    }
}
