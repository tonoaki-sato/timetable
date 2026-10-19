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
     * 入力値の前処理（チェックボックスのboolean化など）
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [
            'is_at_least' => $this->boolean('is_at_least'),
            'is_critical' => $this->boolean('is_critical'),
            'requires_review' => $this->boolean('requires_review'),
            'auto_assign_lead' => $this->boolean('auto_assign_lead'),
        ];

        if ($this->filled('lane_id') && ! $this->filled('lane')) {
            $lane = Lane::find($this->input('lane_id'));
            if ($lane) {
                $mergeData['lane'] = $lane->name;
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
            'timetable_id' => ['required', 'integer', 'exists:timetables,id'],
            'lane_id' => ['nullable', 'integer', 'exists:lanes,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:5000'],
            'lane' => ['required_without:lane_id', 'nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'required_people_count' => ['required', 'integer', 'min:0', 'max:999'],
            'is_at_least' => ['required', 'boolean'],
            'is_critical' => ['required', 'boolean'],
            'requires_review' => ['required', 'boolean'],
            'auto_assign_lead' => ['nullable', 'boolean'],
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
