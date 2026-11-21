<?php

namespace App\Http\Requests;

use App\Models\LayoutArea;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLayoutAreaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $layoutArea = $this->route('layout_area');

        return $layoutArea instanceof LayoutArea
            ? ($this->user()?->can('update', $layoutArea) ?? false)
            : ($this->user()?->can('update', LayoutArea::class) ?? false);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('name') && is_string($this->input('name'))) {
            $merge['name'] = trim($this->input('name'));
        }

        if ($this->has('location_id')) {
            $locationId = $this->input('location_id');
            $merge['location_id'] = ($locationId === '' || $locationId === null) ? null : $locationId;
        }

        if ($this->has('notes')) {
            $notes = $this->input('notes');
            $merge['notes'] = (is_string($notes) && trim($notes) === '') ? null : $notes;
        }

        if ($this->has('is_active')) {
            $merge['is_active'] = $this->boolean('is_active');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $layoutArea = $this->route('layout_area');
        $layoutAreaId = $layoutArea instanceof LayoutArea ? $layoutArea->id : $layoutArea;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('layout_areas', 'name')->ignore($layoutAreaId)],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * バリデーションエラー表示用の属性名
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'エリア名',
            'location_id' => '関連拠点',
            'notes' => '備考・特記事項',
            'is_active' => '有効フラグ',
        ];
    }
}
