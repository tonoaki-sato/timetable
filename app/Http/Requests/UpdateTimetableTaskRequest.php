<?php

namespace App\Http\Requests;

use App\Models\TimetableTask;
use App\Services\TimetableTaskService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTimetableTaskRequest extends FormRequest
{
    /**
     * 認可判定
     */
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof TimetableTask
            ? ($this->user()?->can('update', $task) ?? false)
            : ($this->user()?->can('update', TimetableTask::class) ?? false);
    }

    /**
     * 入力値の前処理
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_at_least' => $this->boolean('is_at_least'),
            'is_critical' => $this->boolean('is_critical'),
            'status' => $this->input('status', 'not_started'),
        ]);
    }

    /**
     * バリデーションルール
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $statusKeys = implode(',', array_keys(TimetableTask::STATUSES));

        return [
            'title' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'required_people_count' => ['required', 'integer', 'min:0', 'max:999'],
            'is_at_least' => ['required', 'boolean'],
            'is_critical' => ['required', 'boolean'],
            'status' => ['required', 'string', 'in:'.$statusKeys],
            'content' => ['nullable', 'string', 'max:5000'],
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
            'title' => '作業タスク名',
            'location' => '実施場所',
            'start_time' => '開始時刻',
            'end_time' => '終了時刻',
            'required_people_count' => '必要人員数',
            'is_at_least' => '〇〇名以上フラグ',
            'is_critical' => '最優先フラグ',
            'status' => '進行ステータス',
            'content' => '作業内容・手順指示',
        ];
    }

    /**
     * 時間帯重複の検証
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var TimetableTask $task */
            $task = $this->route('task');
            if (! $task instanceof TimetableTask) {
                return;
            }

            $taskService = app(TimetableTaskService::class);
            $taskData = [
                [
                    'id' => $task->id,
                    'title' => (string) $this->input('title'),
                    'start_time' => (string) $this->input('start_time'),
                    'end_time' => (string) $this->input('end_time'),
                    'status' => (string) $this->input('status', 'not_started'),
                ],
            ];

            // 自身以外の同一レーン既存タスクとの時間帯重複チェック
            $dbErrors = $taskService->checkDatabaseOverlaps($task->lane_id, $taskData, [$task->id]);
            foreach ($dbErrors as $err) {
                $validator->errors()->add('start_time', $err);
            }
        });
    }
}
