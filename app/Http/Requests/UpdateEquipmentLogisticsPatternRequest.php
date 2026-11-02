<?php

namespace App\Http\Requests;

use App\Models\EquipmentLogisticsPattern;
use App\Models\EquipmentLogisticsPatternStep;
use App\Models\EquipmentLogisticsPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentLogisticsPatternRequest extends FormRequest
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
            'steps.*.quantity_rule' => ['required', 'string', Rule::in(array_keys(EquipmentLogisticsPatternStep::QUANTITY_RULES))],
            'steps.*.quantity_note' => ['nullable', 'string', 'max:255'],
            'steps.*.start_time' => ['nullable', 'string', 'max:10'],
            'steps.*.end_time' => ['nullable', 'string', 'max:10'],
            'steps.*.from_location_role' => ['required', 'string', Rule::in(array_keys(EquipmentLogisticsPatternStep::LOCATION_ROLES))],
            'steps.*.to_location_role' => ['required', 'string', Rule::in(array_keys(EquipmentLogisticsPatternStep::LOCATION_ROLES))],
            'steps.*.from_location_id' => ['nullable', 'exists:locations,id'],
            'steps.*.to_location_id' => ['nullable', 'exists:locations,id'],
            'steps.*.from_location' => ['nullable', 'string', 'max:255'],
            'steps.*.to_location' => ['nullable', 'string', 'max:255'],
            'steps.*.operator_type' => ['required', 'string', Rule::in(array_keys(EquipmentLogisticsPlan::OPERATOR_TYPES))],
            'steps.*.witness_required' => ['nullable', 'boolean'],
            'steps.*.notes' => ['nullable', 'string', 'max:2000'],
            'steps.*.display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

