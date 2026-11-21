<?php

namespace App\Http\Requests;

use App\Models\Timetable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DestroyTimetableLaneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
        $timetableId = $timetable?->id;

        return [
            'migrate_to_timetable_lane_id' => [
                'nullable',
                'integer',
                $timetableId !== null
                    ? Rule::exists('timetable_lanes', 'id')->where('timetable_id', $timetableId)
                    : 'exists:timetable_lanes,id',
            ],
            'cascade_delete_tasks' => ['nullable', 'boolean'],
        ];
    }
}
