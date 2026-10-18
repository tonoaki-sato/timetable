<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * 認可判定
     */
    public function authorize(): bool
    {
        /** @var User $targetUser */
        $targetUser = $this->route('user');

        return $this->user()?->can('update', $targetUser) ?? false;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $targetUser */
        $targetUser = $this->route('user');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($targetUser->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        // 管理者のみ権限ロールと有効状態の変更をバリデーション
        if ($this->user()?->isAdmin()) {
            $rules['role'] = ['required', 'string', 'in:admin,operator'];
            $rules['is_active'] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    /**
     * 属性名の日本語化
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '氏名',
            'email' => 'メールアドレス',
            'password' => 'パスワード',
            'role' => '権限ロール',
            'is_active' => 'アカウント有効状態',
        ];
    }
}
