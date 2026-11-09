<?php

namespace App\Http\Requests;

use App\Enums\EquipmentCheckoutRule;
use App\Enums\EquipmentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(EquipmentType::class)],
            'checkout_rule' => ['nullable', Rule::enum(EquipmentCheckoutRule::class)],
            'quantity' => ['required', 'integer', 'min:1'],
            'is_approximate_quantity' => ['boolean'],
            'unit' => ['required', 'string', 'max:20'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'storage_detail' => ['nullable', 'string', 'max:255'],
            'lane_id' => ['nullable', 'exists:lanes,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
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

        if ($this->input('type') === 'rental') {
            $merged['storage_location'] = null;
            $merged['storage_detail'] = null;
        }

        $this->merge($merged);
    }
}
