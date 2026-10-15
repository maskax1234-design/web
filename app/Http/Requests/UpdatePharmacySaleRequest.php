<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePharmacySaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessPharmacy() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.medicine_id' => ['required', 'integer', 'distinct', 'exists:medicines,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'discount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'tax_percent' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999.99'],
        ];
    }
}
