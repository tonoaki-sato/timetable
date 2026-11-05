<?php

namespace App\Http\Requests;

use App\Models\Lane;
use App\Models\TimetableTask;
use App\Services\TimetableTaskService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkUpdateTimetableTaskRequest extends FormRequest
{
    /**
     * 認可判定（作業タスク更新権限を持つユーザーのみ）
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', TimetableTask::class) ?? false;
    }

    /**
     * 入力値の前処理（チェックボックスのboolean化・配列データの正規化）
     */
    protected function prepareForValidation(): void
    {
        if (is_array($this->input('tasks'))) {
            $normalizedTasks = [];
            foreach ($this->input('tasks') as $index => $taskItem) {
                if (! is_array($taskItem)) {
                    continue;
                }
                $normalizedTasks[$index] = array_merge($taskItem, [
                    'id' => ! empty($taskItem['id']) ? (int) $taskItem['id'] : null,
                    'is_at_least' => filter_var($taskItem['is_at_least'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'is_critical' => filter_var($taskItem['is_critical'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'status' => $taskItem['status'] ?? 'not_started',
                ]);
            }
            $this->merge(['tasks' => array_values($normalizedTasks)]);
        }
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
            'tasks' => ['required', 'array', 'min:1', 'max:100'],
            'tasks.*.id' => ['nullable', 'integer', 'exists:timetable_tasks,id'],
            'tasks.*.title' => ['required', 'string', 'max:255'],
            'tasks.*.location' => ['nullable', 'string', 'max:255'],
            'tasks.*.start_time' => ['required', 'date_format:H:i'],
            'tasks.*.end_time' => ['required', 'date_format:H:i', 'after:tasks.*.start_time'],
            'tasks.*.required_people_count' => ['required', 'integer', 'min:0', 'max:999'],
            'tasks.*.is_at_least' => ['required', 'boolean'],
            'tasks.*.is_critical' => ['required', 'boolean'],
            'tasks.*.status' => ['required', 'string', 'in:'.$statusKeys],
            'tasks.*.content' => ['nullable', 'string', 'max:5000'],
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
            'tasks' => '作業タスクリスト',
            'tasks.*.title' => '作業タスク名',
            'tasks.*.location' => '実施場所',
            'tasks.*.start_time' => '開始時刻',
            'tasks.*.end_time' => '終了時刻',
            'tasks.*.required_people_count' => '必要人員数',
            'tasks.*.is_at_least' => '〇〇名以上フラグ',
            'tasks.*.is_critical' => '最優先フラグ',
            'tasks.*.status' => '進行ステータス',
            'tasks.*.content' => '作業内容・手順指示',
        ];
    }

    /**
     * バリデータの追加設定（時間帯重複の検証）
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $tasks = $this->input('tasks');
            if (! is_array($tasks) || empty($tasks)) {
                return;
            }

            $taskService = app(TimetableTaskService::class);

            // 1. 送信タスク内での重複チェック
            $internalErrors = $taskService->checkInternalOverlaps($tasks);
            foreach ($internalErrors as $err) {
                $validator->errors()->add('tasks', $err);
            }

            // 2. 送信対象以外の既存タスクとの重複チェック
            $lane = $this->route('lane');
            $laneId = $lane instanceof Lane ? $lane->id : (is_numeric($lane) ? (int) $lane : null);
            if ($laneId) {
                $submittedIds = array_filter(array_column($tasks, 'id'));
                $dbErrors = $taskService->checkDatabaseOverlaps($laneId, $tasks, $submittedIds);
                foreach ($dbErrors as $err) {
                    $validator->errors()->add('tasks', $err);
                }
            }
        });
    }
}
