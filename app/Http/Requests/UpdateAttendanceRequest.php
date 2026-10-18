<?php

namespace App\Http\Requests;

use App\Models\TaskStaffAssignment;
use App\Models\TimetableTask;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
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
            'attendance_status' => ['required', 'string', 'in:'.implode(',', array_keys(TaskStaffAssignment::ATTENDANCE_STATUSES))],
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
            'attendance_status' => '出欠状況',
        ];
    }
}
