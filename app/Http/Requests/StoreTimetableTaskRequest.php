<?php

namespace App\Http\Requests;

use App\Models\Lane;
use App\Models\TimetableTask;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
     * 入力値の前処理（チェックボックスのboolean化・単一/複数タスク形式の正規化）
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [
            'auto_assign_lead' => $this->boolean('auto_assign_lead'),
        ];

        if (! $this->filled('lane_id') && $this->filled('lane')) {
            $timetableId = $this->input('timetable_id');
            $laneName = (string) $this->input('lane');
            $cleanName = str_replace('班', '', $laneName);
            $lane = Lane::where('timetable_id', $timetableId)
                ->where(function ($q) use ($laneName, $cleanName) {
                    $q->where('name', $laneName)->orWhere('name', $cleanName);
                })->first();
            if ($lane) {
                $mergeData['lane_id'] = $lane->id;
            }
        }

        // 複数タスク配列形式が送信された場合の各要素の真偽値・初期値整形
        if (is_array($this->input('tasks'))) {
            $normalizedTasks = [];
            foreach ($this->input('tasks') as $index => $taskItem) {
                if (! is_array($taskItem)) {
                    continue;
                }
                $normalizedTasks[$index] = array_merge($taskItem, [
                    'is_at_least' => filter_var($taskItem['is_at_least'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'is_critical' => filter_var($taskItem['is_critical'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'requires_review' => filter_var($taskItem['requires_review'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'status' => $taskItem['status'] ?? 'not_started',
                ]);
            }
            $mergeData['tasks'] = array_values($normalizedTasks);
        } elseif ($this->hasAny(['title', 'start_time', 'end_time'])) {
            // 後方互換: 従来の単一タスクフラット形式が送信された場合は tasks[0] に正規化
            $mergeData['tasks'] = [
                [
                    'title' => $this->input('title'),
                    'content' => $this->input('content'),
                    'location' => $this->input('location'),
                    'start_time' => $this->input('start_time'),
                    'end_time' => $this->input('end_time'),
                    'required_people_count' => $this->input('required_people_count', 0),
                    'is_at_least' => $this->boolean('is_at_least'),
                    'is_critical' => $this->boolean('is_critical'),
                    'requires_review' => $this->boolean('requires_review'),
                    'status' => $this->input('status', 'not_started'),
                ],
            ];
        }

        $this->merge($mergeData);
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
            'timetable_id' => ['required', 'integer', 'exists:timetables,id'],
            'lane_id' => ['required', 'integer', 'exists:lanes,id'],
            'lane' => ['nullable', 'string', 'max:100'],
            'auto_assign_lead' => ['nullable', 'boolean'],
            'tasks' => ['required', 'array', 'min:1', 'max:50'],
            'tasks.*.title' => ['required', 'string', 'max:255'],
            'tasks.*.content' => ['nullable', 'string', 'max:5000'],
            'tasks.*.location' => ['nullable', 'string', 'max:255'],
            'tasks.*.start_time' => ['required', 'date_format:H:i'],
            'tasks.*.end_time' => ['required', 'date_format:H:i', 'after:tasks.*.start_time'],
            'tasks.*.required_people_count' => ['required', 'integer', 'min:0', 'max:999'],
            'tasks.*.is_at_least' => ['required', 'boolean'],
            'tasks.*.is_critical' => ['required', 'boolean'],
            'tasks.*.requires_review' => ['required', 'boolean'],
            'tasks.*.status' => ['required', 'string', 'in:'.$statusKeys],
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
            'lane_id' => '担当班（レーン）',
            'lane' => '担当班（レーン）',
            'tasks' => '作業タスクリスト',
            'tasks.*.title' => '作業タスク名',
            'tasks.*.content' => '作業内容・手順指示',
            'tasks.*.location' => '実施場所',
            'tasks.*.start_time' => '開始時刻',
            'tasks.*.end_time' => '終了時刻',
            'tasks.*.required_people_count' => '必要人員数',
            'tasks.*.is_at_least' => '〇〇名以上フラグ',
            'tasks.*.is_critical' => '最優先フラグ',
            'tasks.*.requires_review' => '本部確認フラグ',
            'tasks.*.status' => '進行ステータス',
        ];
    }
}
