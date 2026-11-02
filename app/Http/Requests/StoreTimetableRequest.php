<?php

namespace App\Http\Requests;

use App\Models\Timetable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTimetableRequest extends FormRequest
{
    /**
     * 認可判定
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Timetable::class) ?? false;
    }

    /**
     * バリデーション前処理（空の時間帯行をフィルタリング）
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('road_periods') && is_array($this->road_periods)) {
            $filtered = array_values(array_filter($this->road_periods, function ($item) {
                return ! empty($item['start_time']) || ! empty($item['end_time']) || ! empty($item['label']);
            }));
            $this->merge(['road_periods' => empty($filtered) ? null : $filtered]);
        }

        if ($this->has('festival_periods') && is_array($this->festival_periods)) {
            $filtered = array_values(array_filter($this->festival_periods, function ($item) {
                return ! empty($item['start_time']) || ! empty($item['end_time']) || ! empty($item['label']);
            }));
            $this->merge(['festival_periods' => empty($filtered) ? null : $filtered]);
        }

        if ($this->has('create_default_lanes')) {
            $this->merge([
                'create_default_lanes' => $this->boolean('create_default_lanes'),
            ]);
        }
    }

    /**
     * バリデーションルール
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_date' => ['required', 'date', 'unique:timetables,target_date'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(Timetable::STATUSES))],
            'road_periods' => ['nullable', 'array'],
            'road_periods.*.start_time' => ['required_with:road_periods', 'date_format:H:i'],
            'road_periods.*.end_time' => ['required_with:road_periods', 'date_format:H:i', 'after:road_periods.*.start_time'],
            'road_periods.*.label' => ['required_with:road_periods', 'string', 'max:50'],
            'road_periods.*.status' => ['nullable', 'string', 'max:30'],
            'festival_periods' => ['nullable', 'array'],
            'festival_periods.*.start_time' => ['required_with:festival_periods', 'date_format:H:i'],
            'festival_periods.*.end_time' => ['required_with:festival_periods', 'date_format:H:i', 'after:festival_periods.*.start_time'],
            'festival_periods.*.label' => ['required_with:festival_periods', 'string', 'max:50'],
            'festival_periods.*.status' => ['nullable', 'string', 'max:30'],
            'create_default_lanes' => ['nullable', 'boolean'],
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
            'target_date' => '開催日',
            'name' => '運行日名称',
            'description' => '概要・全体注意事項',
            'start_time' => '運行開始時刻',
            'end_time' => '運行終了時刻',
            'status' => 'ステータス',
            'road_periods' => '道路状況設定',
            'road_periods.*.start_time' => '道路規制の開始時刻',
            'road_periods.*.end_time' => '道路規制の終了時刻',
            'road_periods.*.label' => '道路規制の表示文言',
            'festival_periods' => 'まつり状況設定',
            'festival_periods.*.start_time' => 'まつり状況の開始時刻',
            'festival_periods.*.end_time' => 'まつり状況の終了時刻',
            'festival_periods.*.label' => 'まつり状況の表示文言',
        ];
    }
}
