<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

trait WithSorting
{
    #[Url(history: true)]
    public $sortField = 'created_at';

    #[Url(history: true)]
    public $sortDirection = 'desc';

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
            $this->sortField = $field;
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
