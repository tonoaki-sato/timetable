<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLogisticsLocationRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:parking,facility,open_space,other'],
            'is_all_day' => ['boolean'],
            'available_from' => ['nullable', 'date'],
            'available_to' => ['nullable', 'date', 'after_or_equal:available_from'],
            'description' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [
            'is_all_day' => $this->boolean('is_all_day'),
        ];

        if ($this->has('is_active')) {
            $data['is_active'] = $this->boolean('is_active');
        }

        $this->merge($data);
    }
}
