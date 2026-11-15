<?php

namespace App\Http\Requests;

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
            'status' => ['required', Rule::in(['予定', '確定'])],
            'layout_area_id' => ['nullable', 'exists:layout_areas,id'],
            'location_description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'facility_code' => '設備コード',
            'name' => '設備名称',
            'facility_type' => '設備種別',
            'status' => '設置ステータス',
            'layout_area_id' => '設置エリア',
            'location_description' => '設置場所の目安',
            'notes' => '特記事項',
        ];
    }
}
