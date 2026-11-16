<?php

namespace App\Http\Requests;

use App\Enums\LayoutItemStatus;
use App\Enums\LayoutItemType;
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
        return $this->user() !== null;
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
            'layout_area_id' => ['nullable', 'exists:layout_areas,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(LayoutItemStatus::class)],
            'category' => ['nullable', 'string', 'max:50'],
            'sub_type' => ['nullable', 'string', 'max:50'],
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
            'sub_type' => '形態・詳細種別',
            'vendor_id' => '出展・出店者',
            'location_description' => '近接店舗・設置場所の目安',
            'notes' => '内容・特記事項・メモ',
        ];
    }
}
