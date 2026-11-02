<?php

namespace App\Http\Requests;

use App\Models\EquipmentLogisticsPattern;
use App\Models\EquipmentLogisticsPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentLogisticsPatternRequest extends FormRequest
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
            'target_type' => ['required', 'string', Rule::in(array_keys(EquipmentLogisticsPattern::TARGET_TYPES))],
            'description' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.day_phase' => ['required', 'string', Rule::in(array_keys(EquipmentLogisticsPlan::DAY_PHASES))],
            'steps.*.action_type' => ['required', 'string', Rule::in(array_keys(EquipmentLogisticsPlan::ACTION_TYPES))],
            'steps.*.title' => ['required', 'string', 'max:255'],
            'steps.*.from_location_id' => ['nullable', 'exists:locations,id'],
            'steps.*.to_location_id' => ['nullable', 'exists:locations,id'],
            'steps.*.operator_type' => ['required', 'string', Rule::in(array_keys(EquipmentLogisticsPlan::OPERATOR_TYPES))],
            'steps.*.witness_required' => ['nullable', 'boolean'],
            'steps.*.notes' => ['nullable', 'string', 'max:2000'],
            'steps.*.display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
