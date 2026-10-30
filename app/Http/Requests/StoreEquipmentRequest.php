<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:committee,rental'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit' => ['required', 'string', 'max:20'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'event_company_name' => ['nullable', 'string', 'max:255'],
            'event_company_contact' => ['nullable', 'string', 'max:255'],
            'lane_id' => ['nullable', 'exists:lanes,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'template_type' => ['nullable', 'string', 'in:none,pattern_a,pattern_b,pattern_c'],
            'staging_location_id' => ['nullable', 'exists:logistics_locations,id'],
            'night_location_1_id' => ['nullable', 'exists:logistics_locations,id'],
            'night_location_2_id' => ['nullable', 'exists:logistics_locations,id'],
            'witness_staff_id' => ['nullable', 'exists:staff,id'],
        ];
    }
}
