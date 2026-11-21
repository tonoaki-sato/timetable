<?php

namespace App\Http\Requests;

use App\Models\TimetableLane;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTimetableLaneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $timetableLane = $this->route('timetableLane');

        return $timetableLane instanceof TimetableLane
            ? ($this->user()?->can('update', $timetableLane) ?? false)
            : ($this->user()?->can('update', TimetableLane::class) ?? false);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('lead_staff_id')) {
            $leadStaffId = $this->input('lead_staff_id');
            $merge['lead_staff_id'] = ($leadStaffId === '' || $leadStaffId === null) ? null : $leadStaffId;
        }

        if ($this->has('notes')) {
            $notes = $this->input('notes');
            $merge['notes'] = (is_string($notes) && trim($notes) === '') ? null : $notes;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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
            'lead_staff_id' => '班長（主担当）',
            'fixed_staff_ids' => '固定スタッフ',
            'notes' => '当日運用メモ',
        ];
    }
}
