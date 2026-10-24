<?php

namespace App\Http\Requests;

use App\Models\TimetableTask;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkAssignStaffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('task') ?? $this->route('timetable_task');

        return $task instanceof TimetableTask
            ? ($this->user()?->can('update', $task) ?? false)
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
            'staff_ids' => ['required', 'array', 'min:1'],
            'staff_ids.*' => ['integer', 'exists:staff,id'],
            'role_description' => ['nullable', 'string', 'max:255'],
            'force' => ['nullable', 'boolean'],
        ];
    }

    /**
     * 属性名の日本語化
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'staff_ids' => '担当スタッフ一覧',
            'staff_ids.*' => '担当スタッフ',
            'role_description' => '担当役割・詳細役割',
        ];
    }
}
