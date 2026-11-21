<?php

namespace App\Http\Requests;

use App\Models\LayoutArea;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderLayoutAreasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', LayoutArea::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'layout_area_ids' => ['required', 'array'],
            'layout_area_ids.*' => ['required', 'integer', 'exists:layout_areas,id'],
        ];
    }
}
