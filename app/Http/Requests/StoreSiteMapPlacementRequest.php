<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSiteMapPlacementRequest extends FormRequest
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
        return [
            'shape_type' => ['required', 'string', 'max:50'],
            'display_text' => ['nullable', 'string', 'max:255'],
            'color_theme' => ['nullable', 'string', 'max:30'],
            'pos_x' => ['required', 'numeric'],
            'pos_y' => ['required', 'numeric'],
            'width' => ['nullable', 'numeric', 'min:1'],
            'height' => ['nullable', 'numeric', 'min:1'],
            'rotation' => ['nullable', 'integer'],
            'is_rotation_locked' => ['nullable', 'boolean'],
            'layout_item_id' => ['nullable', 'integer', 'exists:layout_items,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
