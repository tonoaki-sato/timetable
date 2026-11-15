<?php

namespace App\Http\Requests;

use App\Enums\FacilityType;
use App\Models\LayoutFacility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLayoutFacilityRequest extends FormRequest
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
        $facility = $this->route('facility');
        $facilityModel = $facility instanceof LayoutFacility ? $facility : LayoutFacility::find($facility);
        $mapId = $facilityModel?->layout_map_id;

        return [
            'facility_code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('layout_facilities', 'facility_code')
                    ->where('layout_map_id', $mapId)
                    ->ignore($facilityModel?->id),
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
