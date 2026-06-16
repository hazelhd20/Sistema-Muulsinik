<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return clone $this->user()->hasPermission('requisiciones.editar') || clone $this->user()->hasPermission('*');
    }

    public function rules(): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'exists:projects,id'],
            'vendor_id' => ['nullable', 'exists:suppliers,id'],
            'date' => ['sometimes', 'required', 'date'],
            'annotations' => ['nullable', 'string', 'max:1000'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.measure_id' => ['required_with:items', 'integer', 'exists:measures,id'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_source' => ['nullable', 'string', 'max:255'],
        ];
    }
}
