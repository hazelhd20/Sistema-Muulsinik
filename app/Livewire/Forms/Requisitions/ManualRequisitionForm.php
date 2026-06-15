<?php

namespace App\Livewire\Forms\Requisitions;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ManualRequisitionForm extends Form
{
    public $projectId = '';
    public $vendorId = '';
    public string $annotations = '';
    public string $date = '';
    public array $items = [];

    public function rules()
    {
        return [
            'projectId' => 'required|exists:projects,id',
            'vendorId' => 'nullable|exists:vendors,id',
            'annotations' => 'nullable|max:500',
            'date' => 'required|date',
        ];
    }

    public function getValidationAttributes()
    {
        return [
            'projectId' => 'Proyecto',
            'vendorId' => 'Proveedor',
            'annotations' => 'Anotaciones',
            'date' => 'Fecha',
        ];
    }

    public function validateForm()
    {
        $this->validate();

        if (empty($this->items)) {
            return 'Agrega al menos un producto a la requisición.';
        }

        foreach ($this->items as $i => $item) {
            if (empty(trim($item['name'] ?? ''))) {
                return 'El producto en la fila '.($i + 1).' no tiene nombre.';
            }
            if (($item['quantity'] ?? 0) <= 0) {
                return 'La cantidad en la fila '.($i + 1).' debe ser mayor a 0.';
            }
        }

        return null;
    }

    public function addProduct(array $product)
    {
        $this->items[] = [
            'id' => uniqid(),
            'name' => $product['name'],
            'quantity' => 1,
            'unit' => $product['unit'],
            'unit_price' => $product['last_price'],
            'category_id' => $product['category_id'],
        ];
    }

    public function addManualItem()
    {
        $this->items[] = [
            'id' => uniqid(),
            'name' => '',
            'quantity' => 1,
            'unit' => 'pza',
            'unit_price' => 0,
            'category_id' => null,
        ];
    }

    public function removeItem(int $index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }
}
