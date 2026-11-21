<?php

namespace App\Http\Requests;

use App\Support\Validation\EquipmentValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return EquipmentValidationRules::rules();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $merged = [
            'is_approximate_quantity' => $this->boolean('is_approximate_quantity'),
            'checkout_rule' => $this->input('checkout_rule', 'free') ?: 'free',
        ];

        if ($this->input('type') === 'rental' || $this->input('storage_location_id') === '') {
            $merged['storage_location_id'] = null;
        }

        $this->merge($merged);
    }
}
