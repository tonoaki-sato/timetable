<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApplyEquipmentTemplateRequest extends FormRequest
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
            'pattern_id' => ['required', 'integer', 'exists:equipment_logistics_patterns,id'],
            'override_existing' => ['nullable', 'boolean'],
            'staging_location_id' => ['nullable', 'exists:locations,id'],
            'night_location_1_id' => ['nullable', 'exists:locations,id'],
            'night_location_2_id' => ['nullable', 'exists:locations,id'],
            'witness_staff_id' => ['nullable', 'exists:staff,id'],
        ];
    }
}
