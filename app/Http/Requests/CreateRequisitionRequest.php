<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('requisiciones.crear') || $this->user()->hasPermission('*');
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'vendor_id' => ['nullable', 'exists:suppliers,id'],
            'date' => ['required', 'date'],
            'annotations' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.measure_id' => ['required', 'integer', 'exists:measures,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_source' => ['nullable', 'string', 'max:255'],
        ];
    }
}
