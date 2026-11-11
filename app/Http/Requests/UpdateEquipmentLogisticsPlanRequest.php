<?php

namespace App\Http\Requests;

use App\Enums\EquipmentActionType;
use App\Enums\EquipmentDayPhase;
use App\Enums\EquipmentOperatorType;
use App\Enums\EquipmentPlanStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentLogisticsPlanRequest extends FormRequest
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
            'target_date' => ['nullable', 'date'],
            'day_phase' => ['required', Rule::enum(EquipmentDayPhase::class)],
            'action_type' => ['required', Rule::enum(EquipmentActionType::class)],
            'title' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'quantity_note' => ['nullable', 'string', 'max:255'],
            'start_time' => ['nullable', 'string', 'max:10'],
            'end_time' => ['nullable', 'string', 'max:10'],
            'from_location' => ['nullable', 'string', 'max:255'],
            'to_location' => ['nullable', 'string', 'max:255'],
            'from_location_id' => ['nullable', 'exists:locations,id'],
            'to_location_id' => ['nullable', 'exists:locations,id'],
            'operator_type' => ['required', Rule::enum(EquipmentOperatorType::class)],
            'witness_required' => ['boolean'],
            'witness_staff_id' => ['nullable', 'exists:staff,id'],
            'status' => ['sometimes', 'required', Rule::enum(EquipmentPlanStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'witness_required' => $this->boolean('witness_required'),
        ]);
    }
}
