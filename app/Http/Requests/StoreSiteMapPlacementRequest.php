<?php

namespace App\Http\Requests;

use App\Models\SiteBaseMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSiteMapPlacementRequest extends FormRequest
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
            'shape_type' => ['required', 'string', 'max:50'],
            'display_text' => ['nullable', 'string', 'max:255'],
            'font_size' => ['nullable', 'numeric', 'min:2', 'max:36'],
            'color_theme' => ['nullable', 'string', 'max:30'],
            'pos_x' => ['required', 'numeric'],
            'pos_y' => ['required', 'numeric'],
            'width' => ['nullable', 'numeric', 'min:1'],
            'height' => ['nullable', 'numeric', 'min:1'],
            'rotation' => ['nullable', 'integer'],
            'is_rotation_locked' => ['nullable', 'boolean'],
            'show_hygiene_circle' => ['nullable', 'boolean'],
            'arc_start_angle' => ['nullable', 'integer'],
            'arc_end_angle' => ['nullable', 'integer'],
            'z_index' => ['nullable', 'integer'],
            'layout_item_id' => ['nullable', 'integer', 'exists:layout_items,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
