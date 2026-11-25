<?php

namespace App\Http\Requests\ConeMap;

use App\Enums\ConeCalloutColor;
use App\Models\SiteBaseMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConeMapCalloutRequest extends FormRequest
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
            'target_cone_id' => ['nullable', 'integer', 'exists:cone_map_cones,id'],
            'target_barricade_id' => ['nullable', 'integer', 'exists:cone_map_barricades,id'],
            'anchor_x' => ['required', 'numeric'],
            'anchor_y' => ['required', 'numeric'],
            'box_x' => ['required', 'numeric'],
            'box_y' => ['required', 'numeric'],
            'box_width' => ['nullable', 'numeric', 'min:20', 'max:300'],
            'box_height' => ['nullable', 'numeric', 'min:12', 'max:200'],
            'title' => ['nullable', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:1000'],
            'color_theme' => ['nullable', 'string', Rule::in(ConeCalloutColor::values())],
            'font_size' => ['nullable', 'numeric', 'min:3', 'max:24'],
        ];
    }
}
