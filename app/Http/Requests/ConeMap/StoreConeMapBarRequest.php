<?php

namespace App\Http\Requests\ConeMap;

use App\Enums\ConeBarStyle;
use App\Models\SiteBaseMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConeMapBarRequest extends FormRequest
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
            'start_cone_id' => ['required', 'integer', 'exists:cone_map_cones,id'],
            'end_cone_id' => ['required', 'integer', 'different:start_cone_id', 'exists:cone_map_cones,id'],
            'bar_style' => ['nullable', 'string', Rule::in(ConeBarStyle::values())],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
