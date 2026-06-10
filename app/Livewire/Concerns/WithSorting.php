<?php

namespace App\Livewire\Concerns;

trait WithSorting
{
    public $sortField = 'created_at';

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
