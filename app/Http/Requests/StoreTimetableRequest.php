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
        ];
    }
}
