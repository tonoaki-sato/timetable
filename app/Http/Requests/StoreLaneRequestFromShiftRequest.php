<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLaneRequestFromShiftRequest extends FormRequest
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
            'request_type' => ['required', 'string', Rule::in(['staffing', 'equipment'])],
            'timetable_task_id' => ['nullable', 'integer', 'exists:timetable_tasks,id'],
            'requested_count' => ['required', 'integer', 'min:1', 'max:100'],
            'equipment_name' => ['required_if:request_type,equipment', 'nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
