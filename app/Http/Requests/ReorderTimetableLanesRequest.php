<?php

namespace App\Http\Requests;

use App\Models\Timetable;
use App\Models\TimetableLane;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderTimetableLanesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', TimetableLane::class) ?? false;
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
            'timetable_lane_ids' => ['required', 'array'],
            'timetable_lane_ids.*' => [
                'required',
                'integer',
                $timetableId !== null
                    ? Rule::exists('timetable_lanes', 'id')->where('timetable_id', $timetableId)
                    : 'exists:timetable_lanes,id',
            ],
        ];
    }
}
