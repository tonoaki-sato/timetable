<?php

namespace App\Http\Requests;

use App\Models\LayoutArea;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLayoutAreaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', LayoutArea::class) ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $locationId = $this->input('location_id');
        $notes = $this->input('notes');

        $this->merge([
            'name' => is_string($name) ? trim($name) : $name,
            'location_id' => ($locationId === '' || $locationId === null) ? null : $locationId,
            'notes' => (is_string($notes) && trim($notes) === '') ? null : $notes,
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:layout_areas,name'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
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
