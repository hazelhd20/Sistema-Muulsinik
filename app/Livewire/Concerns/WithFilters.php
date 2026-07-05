<?php

namespace App\Livewire\Concerns;

trait WithFilters
{
    use WithPerPagePagination;

    /**
     * Propiedades de filtro por defecto. Los componentes pueden sobreescribir esto
     * definiendo una propiedad $filterableProperties.
     */
    protected function getFilterableProperties(): array
    {
        return property_exists($this, 'filterableProperties') 
            ? $this->filterableProperties 
            : [
                'search', 'statusFilter', 'periodFilter', 'dateFrom', 'dateTo', 
                'categoryFilter', 'projectFilter', 'creatorFilter', 'vendorFilter', 
                'userFilter', 'roleFilter', 'measureFilter', 'tab', 'usageFilter', 'trashedFilter', 'typeFilter'
            ];
    }

    public function updatedWithFilters($property)
    {
        if (in_array($property, $this->getFilterableProperties())) {
            $this->resetPaginationAndSelection();
        }
    }

    public function clearAllFilters(): void
    {
        // Solo resetear las propiedades que existen en este componente
        $propertiesToReset = array_filter(
            $this->getFilterableProperties(), 
            fn($prop) => property_exists($this, $prop)
        );
        
        if (!empty($propertiesToReset)) {
            $this->reset($propertiesToReset);
        }
        
        $this->resetPaginationAndSelection();
    }
    
    protected function resetPaginationAndSelection(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
        if (property_exists($this, 'selectedRows')) {
            $this->selectedRows = [];
        }
        if (property_exists($this, 'allSelected')) {
            $this->allSelected = false;
        }
    }
}
