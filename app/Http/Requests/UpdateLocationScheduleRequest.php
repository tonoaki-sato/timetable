<?php

namespace App\Http\Requests;

use App\Models\LocationSchedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $schedule = $this->route('schedule');

        return $schedule instanceof LocationSchedule
            ? ($this->user()?->can('update', $schedule) ?? false)
            : ($this->user()?->can('update', LocationSchedule::class) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'application_status' => ['required', Rule::in(array_keys(LocationSchedule::APPLICATION_STATUSES))],
            'title' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
