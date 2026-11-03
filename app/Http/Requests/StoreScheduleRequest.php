<?php

namespace App\Http\Requests;

use App\Models\Schedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Schedule::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(Schedule::CATEGORIES))],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(Schedule::STATUSES))],
            'description' => ['nullable', 'string'],
            'notice_note' => ['nullable', 'string'],
            'staff_id' => ['nullable', 'integer', 'exists:staff,id'],
        ];
    }

    /**
     * バリデーションエラーメッセージの属性名
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'category' => '区分（カテゴリ）',
            'start_date' => '開始日',
            'end_date' => '終了日',
            'start_time' => '開始時刻',
            'end_time' => '終了時刻',
            'location' => '場所',
            'status' => 'ステータス',
            'description' => '詳細説明・議題',
            'notice_note' => '共有告知特記メモ',
            'staff_id' => '担当・主催スタッフ',
        ];
    }
}
