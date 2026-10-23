<?php

namespace App\Http\Requests;

use App\Models\Lane;
use App\Models\TimetableTask;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTimetableTaskRequest extends FormRequest
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
     * 入力値の前処理
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [
            'is_at_least' => $this->boolean('is_at_least'),
            'is_critical' => $this->boolean('is_critical'),
            'requires_review' => $this->boolean('requires_review'),
        ];

        if (! $this->filled('lane_id') && $this->filled('lane')) {
            /** @var TimetableTask|null $task */
            $task = $this->route('timetable_task') ?? $this->route('task');
            $timetableId = $this->input('timetable_id', $task?->timetable_id);
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

        $this->merge($mergeData);
    }

    /**
     * バリデーションルール
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'timetable_id' => ['sometimes', 'required', 'integer', 'exists:timetables,id'],
            'lane_id' => ['sometimes', 'required', 'integer', 'exists:lanes,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:5000'],
            'lane' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'required_people_count' => ['required', 'integer', 'min:0', 'max:999'],
            'is_at_least' => ['required', 'boolean'],
            'is_critical' => ['required', 'boolean'],
            'requires_review' => ['required', 'boolean'],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(TimetableTask::STATUSES))],
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
            'title' => '作業タスク名',
            'content' => '作業内容・手順指示',
            'lane' => '担当班（レーン）',
            'location' => '実施場所',
            'start_time' => '開始時刻',
            'end_time' => '終了時刻',
            'required_people_count' => '必要人員数',
            'is_at_least' => '〇〇名以上フラグ',
            'is_critical' => '最優先フラグ',
            'requires_review' => '本部確認フラグ',
            'status' => '進行ステータス',
        ];
    }
}
