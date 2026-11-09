<?php

namespace App\Http\Requests;

use App\Enums\EquipmentActionType;
use App\Enums\EquipmentDayPhase;
use App\Enums\EquipmentOperatorType;
use App\Models\EquipmentLogisticsPattern;
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
        $pattern = $this->route('pattern');

        return $pattern instanceof EquipmentLogisticsPattern
            ? ($this->user()?->can('update', $pattern) ?? false)
            : false;
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
            'steps.*.day_phase' => ['required', Rule::enum(EquipmentDayPhase::class)],
            'steps.*.action_type' => ['required', Rule::enum(EquipmentActionType::class)],
            'steps.*.title' => ['required', 'string', 'max:255'],
            'steps.*.from_location_id' => ['nullable', 'exists:locations,id'],
            'steps.*.to_location_id' => ['nullable', 'exists:locations,id'],
            'steps.*.operator_type' => ['required', Rule::enum(EquipmentOperatorType::class)],
            'steps.*.witness_required' => ['nullable', 'boolean'],
            'steps.*.notes' => ['nullable', 'string', 'max:2000'],
            'steps.*.display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
