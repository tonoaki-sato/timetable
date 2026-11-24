<?php

namespace App\Http\Requests;

use App\Enums\TimetableTaskStatus;
use App\Enums\TimetableTaskType;
use App\Models\TimetableTask;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimetableTaskRequest extends FormRequest
{
    /**
     * 認可判定
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', TimetableTask::class) ?? false;
    }

    /**
     * 入力値の前処理（チェックボックスのboolean化・初期値補完）
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'task_type' => $this->input('task_type') ?: TimetableTaskType::Spot->value,
            'is_at_least' => $this->boolean('is_at_least'),
            'is_critical' => $this->boolean('is_critical'),
            'status' => $this->input('status') ?: 'not_started',
            'auto_assign_lead' => $this->has('auto_assign_lead')
                ? $this->boolean('auto_assign_lead')
                : true,
            'force_assign' => $this->boolean('force_assign'),
        ]);
    }

    /**
     * バリデーションルール
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $timetableId = $this->integer('timetable_id');

        return [
            'timetable_id' => ['required', 'integer', 'exists:timetables,id'],
            'timetable_lane_id' => [
                'required',
                'integer',
                Rule::exists('timetable_lanes', 'id')->where('timetable_id', $timetableId),
            ],
            'task_type' => ['required', 'string', Rule::in(TimetableTaskType::values())],
            'auto_assign_lead' => ['required', 'boolean'],
            'assign_staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'force_assign' => ['nullable', 'boolean'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'required_people_count' => ['required', 'integer', 'min:0', 'max:999'],
            'is_at_least' => ['required', 'boolean'],
            'is_critical' => ['required', 'boolean'],
            'status' => ['required', 'string', Rule::in(TimetableTaskStatus::values())],
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
            'timetable_id' => '対象タイムテーブル',
            'timetable_lane_id' => '担当班',
            'task_type' => 'タスク種別',
            'assign_staff_id' => '初期アサインスタッフ',
            'title' => '作業タスク名',
            'content' => '作業内容・手順指示',
            'location' => '実施場所',
            'start_time' => '開始時刻',
            'end_time' => '終了時刻',
            'required_people_count' => '必要人員数',
            'is_at_least' => '〇〇名以上フラグ',
            'is_critical' => '最優先フラグ',
            'status' => '進行ステータス',
        ];
    }
}
