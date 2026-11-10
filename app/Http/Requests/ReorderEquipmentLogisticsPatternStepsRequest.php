<?php

namespace App\Http\Requests;

use App\Models\EquipmentLogisticsPattern;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderEquipmentLogisticsPatternStepsRequest extends FormRequest
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
            'step_ids' => ['required', 'array'],
            'step_ids.*' => ['required', 'integer', 'exists:equipment_logistics_pattern_steps,id'],
        ];
    }
}

