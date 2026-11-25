<?php

namespace App\Http\Requests\ConeMap;

use App\Enums\ConeCalloutColor;
use App\Models\SiteBaseMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConeMapCalloutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', SiteBaseMap::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_cone_id' => ['nullable', 'integer', 'exists:cone_map_cones,id'],
            'target_barricade_id' => ['nullable', 'integer', 'exists:cone_map_barricades,id'],
            'anchor_x' => ['nullable', 'numeric'],
            'anchor_y' => ['nullable', 'numeric'],
            'box_x' => ['nullable', 'numeric'],
            'box_y' => ['nullable', 'numeric'],
            'box_width' => ['nullable', 'numeric', 'min:20', 'max:300'],
            'box_height' => ['nullable', 'numeric', 'min:12', 'max:200'],
            'title' => ['nullable', 'string', 'max:100'],
            'body' => ['nullable', 'string', 'max:1000'],
            'color_theme' => ['nullable', 'string', Rule::in(ConeCalloutColor::values())],
            'font_size' => ['nullable', 'numeric', 'min:3', 'max:24'],
        ];
    }
}
