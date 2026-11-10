<?php

namespace App\Http\Requests;

use App\Enums\EquipmentActionType;
use App\Enums\EquipmentDayPhase;
use App\Enums\EquipmentOperatorType;
use App\Models\EquipmentLogisticsPattern;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentLogisticsPatternStepRequest extends FormRequest
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
            'day_phase' => ['required', Rule::enum(EquipmentDayPhase::class)],
            'action_type' => ['required', Rule::enum(EquipmentActionType::class)],
            'title' => ['required', 'string', 'max:255'],
            'from_location_id' => ['nullable', 'exists:locations,id'],
            'to_location_id' => ['nullable', 'exists:locations,id'],
            'operator_type' => ['required', Rule::enum(EquipmentOperatorType::class)],
            'witness_required' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
