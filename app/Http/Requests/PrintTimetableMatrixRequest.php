<?php

namespace App\Http\Requests;

use App\Models\Timetable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PrintTimetableMatrixRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $timetable = $this->route('timetable');

        return $timetable instanceof Timetable
            && ($this->user()?->can('view', $timetable) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'preview' => ['nullable', 'boolean'],
            'lane_ids' => ['nullable'],
        ];
    }

    /**
     * 絞り込み対象の担当班ID配列を取得（未指定時は null）
     *
     * @return array<int, int>|null
     */
    public function laneIds(): ?array
    {
        if (! $this->filled('lane_ids')) {
            return null;
        }

        $input = $this->input('lane_ids');
        $rawIds = is_array($input) ? $input : explode(',', (string) $input);

        return array_values(array_filter(
            array_map('intval', $rawIds),
            static fn (int $id): bool => $id > 0
        ));
    }
}
