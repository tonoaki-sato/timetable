<?php

namespace App\Http\Requests;

use App\Models\Location;
use App\Models\LocationSchedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Location::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:locations,name'],
            'location_type_id' => ['required', 'exists:location_types,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_all_day' => ['boolean'],
            'is_active' => ['boolean'],
            'schedules' => ['nullable', 'array'],
            'schedules.*.start_at' => ['required_with:schedules', 'date'],
            'schedules.*.end_at' => ['required_with:schedules', 'date', 'after:schedules.*.start_at'],
            'schedules.*.availability_status' => ['required_with:schedules', Rule::in(array_keys(LocationSchedule::AVAILABILITY_STATUSES))],
        ];
    }

    /**
     * バリデーションエラー表示用の属性名
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '拠点名',
            'location_type_id' => '拠点タイプ',
            'notes' => '特記事項・備考',
            'schedules.*.start_at' => '利用開始日時',
            'schedules.*.end_at' => '利用終了日時',
            'schedules.*.availability_status' => '利用ステータス',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $filteredSchedules = null;
        if ($this->has('schedules') && is_array($this->input('schedules'))) {
            $filteredSchedules = array_values(array_filter($this->input('schedules'), function ($row) {
                return ! empty($row['start_at']) || ! empty($row['end_at']);
            }));
        }

        $mergeData = [
            'is_all_day' => $this->boolean('is_all_day'),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ];

        if ($filteredSchedules !== null) {
            $mergeData['schedules'] = $filteredSchedules;
        }

        $this->merge($mergeData);
    }
}
