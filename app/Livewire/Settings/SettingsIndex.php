<?php

namespace App\Livewire\Settings;

use Livewire\Component;

class SettingsIndex extends Component
{
    public bool $isOpen = false;

    // Tab activa
    public string $activeTab = 'empresa';

    public function render()
    {
        return view('livewire.settings.settings-index');
    }
}
