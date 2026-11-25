<?php

namespace App\Http\Requests\ConeMap;

use App\Enums\ConeBarStyle;
use App\Enums\ConeColor;
use App\Models\SiteBaseMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConeMapConeLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SiteBaseMap::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_x' => ['required', 'numeric'],
            'start_y' => ['required', 'numeric'],
            'end_x' => ['required', 'numeric'],
            'end_y' => ['required', 'numeric'],
            'color' => ['nullable', 'string', Rule::in(ConeColor::values())],
            'bar_style' => ['nullable', 'string', Rule::in(ConeBarStyle::values())],
            'pitch_mode' => ['nullable', 'string', Rule::in(['even_max_2m', 'fixed_2m'])],
            'start_cone_id' => ['nullable', 'integer', 'exists:cone_map_cones,id'],
            'label_prefix' => ['nullable', 'string', 'max:30'],
        ];
    }
}
