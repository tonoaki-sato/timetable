<?php

namespace App\Http\Requests;

use App\Models\TimetableTask;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssignStaffRequest extends FormRequest
{
    /**
     * 認可判定
     */
    public function authorize(): bool
    {
        $task = $this->route('task') ?? $this->route('timetable_task');

        return $task instanceof TimetableTask
            ? ($this->user()?->can('update', $task) ?? false)
            : false;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'staff_id' => ['required', 'integer', 'exists:staff,id'],
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
            'staff_id' => '担当スタッフ',
            'role_description' => '担当役割・詳細役割',
        ];
    }
}
