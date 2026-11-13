<?php

namespace App\Http\Requests;

use App\DTOs\TimetableTask\CreateCommonTimetableTaskDTO;
use App\Models\TimetableTask;
use App\Services\TimetableTaskService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCommonTimetableTaskRequest extends FormRequest
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
            'is_at_least' => $this->boolean('is_at_least'),
            'is_critical' => $this->boolean('is_critical'),
            'auto_assign_lead' => $this->has('auto_assign_lead')
                ? $this->boolean('auto_assign_lead')
                : true,
            'status' => $this->input('status') ?: 'not_started',
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
        $timetableId = $this->integer('timetable_id');

        return [
            '_form' => ['nullable', 'string', 'max:50'],
            'timetable_id' => ['required', 'integer', 'exists:timetables,id'],
            'lane_ids' => ['required', 'array', 'min:1'],
            'lane_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('lanes', 'id')->where('timetable_id', $timetableId),
            ],
            'title' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'required_people_count' => ['required', 'integer', 'min:0', 'max:999'],
            'is_at_least' => ['required', 'boolean'],
            'is_critical' => ['required', 'boolean'],
            'status' => ['required', 'string', 'in:'.$statusKeys],
            'location' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:5000'],
            'auto_assign_lead' => ['required', 'boolean'],
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
            'lane_ids' => '対象担当班',
            'lane_ids.*' => '対象担当班',
            'title' => '作業タスク名',
            'start_time' => '開始時刻',
            'end_time' => '終了時刻',
            'required_people_count' => '必要人員数',
            'is_at_least' => '〇〇名以上フラグ',
            'is_critical' => '最優先フラグ',
            'status' => '進行ステータス',
            'location' => '実施場所・持ち場',
            'content' => '作業内容・手順指示',
            'auto_assign_lead' => '班長・固定スタッフ自動アサイン',
        ];
    }

    /**
     * カスタムエラーメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lane_ids.required' => '登録対象の担当班を1つ以上選択してください。',
            'lane_ids.min' => '登録対象の担当班を1つ以上選択してください。',
            'lane_ids.*.exists' => '選択された担当班の中に、この運行日に属さない班が含まれています。',
        ];
    }

    /**
     * バリデータの追加設定（対象全班での時間帯重複検証）
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $rawLaneIds = $this->input('lane_ids', []);
            if (! is_array($rawLaneIds) || empty($rawLaneIds)) {
                return;
            }

            $laneIds = array_values(array_map('intval', $rawLaneIds));
            $timetableId = $this->integer('timetable_id');
            $startTime = (string) $this->input('start_time');
            $endTime = (string) $this->input('end_time');
            $status = (string) $this->input('status', 'not_started');

            /** @var TimetableTaskService $taskService */
            $taskService = app(TimetableTaskService::class);
            $overlapErrors = $taskService->checkDatabaseOverlapsForLanes(
                $timetableId,
                $laneIds,
                $startTime,
                $endTime,
                $status
            );

            foreach ($overlapErrors as $err) {
                $validator->errors()->add('lane_ids', $err);
            }
        });
    }

    /**
     * バリデーション済み入力値をDTOへ変換する
     */
    public function toDto(): CreateCommonTimetableTaskDTO
    {
        $validated = $this->validated();

        /** @var list<int> $laneIds */
        $laneIds = array_values(array_map('intval', $validated['lane_ids']));

        return new CreateCommonTimetableTaskDTO(
            timetableId: (int) $validated['timetable_id'],
            laneIds: $laneIds,
            title: (string) $validated['title'],
            startTime: (string) $validated['start_time'],
            endTime: (string) $validated['end_time'],
            requiredPeopleCount: (int) $validated['required_people_count'],
            isAtLeast: (bool) $validated['is_at_least'],
            isCritical: (bool) $validated['is_critical'],
            status: (string) $validated['status'],
            location: isset($validated['location']) && $validated['location'] !== '' ? (string) $validated['location'] : null,
            content: isset($validated['content']) && $validated['content'] !== '' ? (string) $validated['content'] : null,
            autoAssignLead: (bool) $validated['auto_assign_lead'],
        );
    }
}
