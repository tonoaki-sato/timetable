<?php

namespace App\Http\Requests;

use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingRequest extends FormRequest
{
    /**
     * 認可判定
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Meeting::class) ?? false;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'meeting_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'notice_note' => ['nullable', 'string', 'max:1000'],
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
            'title' => '会議・打ち合わせ名',
            'meeting_date' => '開催日',
            'start_time' => '開始時刻',
            'end_time' => '終了時刻',
            'location' => '開催場所',
            'staff_id' => '主催者',
            'description' => '内容・議題',
            'notice_note' => 'LINE告知用特記メモ',
        ];
    }
}
