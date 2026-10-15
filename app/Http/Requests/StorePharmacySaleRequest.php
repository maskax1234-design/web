<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePharmacySaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
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
            'amount_paid' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'payment_method' => ['nullable', 'in:Cash,Card,Bank Transfer,Mobile Money,Insurance,Cheque'],
            'pos_token' => ['required', 'uuid'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ((float) $this->input('amount_paid', 0) > 0 && ! $this->filled('payment_method')) {
                    $validator->errors()->add('payment_method', 'Select a payment method when recording a payment.');
                }
            },
        ];
    }
}
