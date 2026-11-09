<?php

namespace App\Http\Requests;

use App\Models\Location;
use App\Models\LocationSchedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $location = $this->route('location');

        return $location instanceof Location
            ? ($this->user()?->can('update', $location) ?? false)
            : ($this->user()?->can('update', Location::class) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location_type_id' => ['required', 'exists:location_types,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_all_day' => ['boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
            'schedules' => ['nullable', 'array'],
            'schedules.*.id' => ['nullable', 'integer', 'exists:location_schedules,id'],
            'schedules.*.start_at' => ['required_with:schedules', 'date'],
            'schedules.*.end_at' => ['required_with:schedules', 'date', 'after:schedules.*.start_at'],
            'schedules.*.availability_status' => ['required_with:schedules', Rule::in(array_keys(LocationSchedule::AVAILABILITY_STATUSES))],
            'schedules.*.title' => ['nullable', 'string', 'max:255'],
            'schedules.*.note' => ['nullable', 'string', 'max:1000'],
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
                return ! empty($row['start_at']) || ! empty($row['end_at']) || ! empty($row['title']);
            }));
        }

        $mergeData = [
            'is_all_day' => $this->boolean('is_all_day'),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ];

        if ($filteredSchedules !== null) {
            $mergeData['schedules'] = $filteredSchedules;
        }

        if ($this->has('display_order') && $this->input('display_order') !== null && $this->input('display_order') !== '') {
            $mergeData['display_order'] = (int) $this->input('display_order');
        }

        $this->merge($mergeData);
    }
}
