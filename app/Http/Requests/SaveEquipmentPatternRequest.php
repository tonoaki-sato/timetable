<?php

namespace App\Http\Requests;

use App\Models\EquipmentLogisticsPattern;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveEquipmentPatternRequest extends FormRequest
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
            'target_type' => ['nullable', 'string', Rule::in(array_keys(EquipmentLogisticsPattern::TARGET_TYPES))],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
