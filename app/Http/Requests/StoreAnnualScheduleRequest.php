<?php

namespace App\Http\Requests;

use App\Models\AnnualSchedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnnualScheduleRequest extends FormRequest
{
    /**
     * 認可判定
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', AnnualSchedule::class) ?? false;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_year' => ['required', 'integer', 'min:2020', 'max:2050'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(AnnualSchedule::CATEGORIES))],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(AnnualSchedule::STATUSES))],
            'description' => ['nullable', 'string', 'max:5000'],
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
            'target_year' => '対象年度',
            'title' => 'スケジュール・行事名',
            'category' => '区分（カテゴリ）',
            'start_date' => '開始日',
            'end_date' => '終了日',
            'location' => '実施場所',
            'status' => 'ステータス',
            'description' => '詳細・特記事項',
        ];
    }
}
