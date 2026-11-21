<?php

namespace App\Http\Requests;

use App\Enums\LayoutItemStatus;
use App\Enums\LayoutItemType;
use App\Models\LayoutMap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLayoutItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', LayoutMap::class) ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('layout_area_id') === '') {
            $this->merge(['layout_area_id' => null]);
        }

        if ($this->input('vendor_id') === '') {
            $this->merge(['vendor_id' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item_type' => ['required', Rule::enum(LayoutItemType::class)],
            'code' => ['required', 'string', 'max:30'],
            'layout_area_id' => ['nullable', 'integer', Rule::exists('layout_areas', 'id')->where('is_active', true)],
            'name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(LayoutItemStatus::class)],
            'category' => ['nullable', 'string', 'max:50'],
            'booth_type' => ['nullable', 'string', 'max:50'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'location_description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * バリデーションエラーメッセージのカスタマイズ
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'item_type' => '配置種別',
            'code' => '識別コード・コマ番号',
            'layout_area_id' => '所属エリア',
            'name' => '名称・ブース表示名',
            'status' => '配置状態',
            'category' => '区分・大分類',
            'booth_type' => 'ブース形態',
            'vendor_id' => '出展・出店者',
            'location_description' => '近接店舗・設置場所の目安',
            'notes' => '内容・特記事項・メモ',
        ];
    }
}
