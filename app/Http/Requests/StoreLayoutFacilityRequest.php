<?php

namespace App\Http\Requests;

use App\Enums\FacilityStatus;
use App\Enums\FacilityType;
use App\Models\Timetable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLayoutFacilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * 入力値の前処理（日本語表記と英語コード値の互換対応）
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('status')) {
            $status = match ($this->input('status')) {
                '未定', '予定' => 'tentative',
                '確定' => 'confirmed',
                default => $this->input('status'),
            };
            $this->merge(['status' => $status]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $timetable = $this->route('timetable');
        $timetableId = $timetable instanceof Timetable ? $timetable->id : $timetable;

        return [
            'facility_code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('layout_facilities', 'facility_code')
                    ->where(function ($query) use ($timetableId) {
                        return $query->whereIn('layout_map_id', function ($subQuery) use ($timetableId) {
                            $subQuery->select('id')
                                ->from('layout_maps')
                                ->where('timetable_id', $timetableId);
                        });
                    }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'facility_type' => ['required', Rule::enum(FacilityType::class)],
            'status' => ['required', Rule::enum(FacilityStatus::class)],
            'layout_area_id' => ['nullable', 'exists:layout_areas,id'],
            'location_description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'facility_code' => '設備コード',
            'name' => '設備名称',
            'facility_type' => '設備種別',
            'status' => '設置状況',
            'layout_area_id' => '特設エリア',
            'location_description' => '設置場所の目安',
            'notes' => '特記事項',
        ];
    }
}
