<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePharmacyStockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->canAccessModule('pharmacy') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'medicine_id' => ['required', 'integer', 'exists:medicines,id'],
            'batch_number' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'expiry_date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
