<?php

namespace App\Http\Requests;

use App\Models\Timetable;
use App\Models\TimetableLane;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimetableLaneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', TimetableLane::class) ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $leadStaffId = $this->input('lead_staff_id');
        $notes = $this->input('notes');

        $this->merge([
            'lead_staff_id' => ($leadStaffId === '' || $leadStaffId === null) ? null : $leadStaffId,
            'notes' => (is_string($notes) && trim($notes) === '') ? null : $notes,
        ]);
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
        $timetableId = $timetable?->id ?? $this->input('timetable_id');

        return [
            'lane_id' => [
                'required',
                'integer',
                Rule::exists('lanes', 'id')->where('is_active', true),
                Rule::unique('timetable_lanes', 'lane_id')->where('timetable_id', $timetableId),
            ],
            'lead_staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'fixed_staff_ids' => ['nullable', 'array'],
            'fixed_staff_ids.*' => ['integer', 'exists:staff,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
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
            'lane_id' => '担当班マスター',
            'lead_staff_id' => '班長（主担当）',
            'fixed_staff_ids' => '固定スタッフ',
            'notes' => '当日運用メモ',
        ];
    }
}
