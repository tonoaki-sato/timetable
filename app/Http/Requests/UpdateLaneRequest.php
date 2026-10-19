<?php

namespace App\Http\Requests;

use App\Models\Lane;
use App\Models\Timetable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLaneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Lane|null $lane */
        $lane = $this->route('lane');

        return $this->user()?->can('update', $lane ?? Lane::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Timetable|null $timetable */
        $timetable = $this->route('timetable');
        /** @var Lane|null $lane */
        $lane = $this->route('lane');
        $timetableId = $timetable?->id ?? $lane?->timetable_id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('lanes')->where('timetable_id', $timetableId)->ignore($lane?->id),
            ],
            'color' => [
                'required',
                'string',
                Rule::in(array_keys(Lane::COLOR_OPTIONS)),
            ],
            'icon' => ['nullable', 'string', 'max:30'],
            'lead_staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['required', 'integer', 'min:0', 'max:9999'],
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
            'name' => 'レーン名',
            'color' => 'テーマカラー',
            'icon' => 'アイコン',
            'lead_staff_id' => '主担当スタッフ',
            'description' => '業務範囲・説明',
            'display_order' => '表示順',
        ];
    }
}
