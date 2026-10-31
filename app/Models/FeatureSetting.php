<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\FeatureSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'feature_key',
    'name',
    'description',
    'is_enabled',
    'opened_at',
    'closed_at',
    'notice_message',
])]
class FeatureSetting extends Model
{
    /** @use HasFactory<FeatureSettingFactory> */
    use HasFactory;

    /**
     * スタッフセルフ登録機能のキー定数
     */
    public const string FEATURE_STAFF_SELF_REGISTRATION = 'staff_self_registration';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 指定時刻において本機能が利用可能（受付中）かどうかを判定
     */
    public function isOpen(?CarbonInterface $now = null): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        $now = $now ?? now();

        if ($this->opened_at && $now->lt($this->opened_at)) {
            return false;
        }

        if ($this->closed_at && $now->gt($this->closed_at)) {
            return false;
        }

        return true;
    }

    /**
     * 機能のステータス識別文字列を取得 ('open', 'upcoming', 'closed', 'disabled')
     */
    public function getStatus(?CarbonInterface $now = null): string
    {
        if (! $this->is_enabled) {
            return 'disabled';
        }

        $now = $now ?? now();

        if ($this->opened_at && $now->lt($this->opened_at)) {
            return 'upcoming';
        }

        if ($this->closed_at && $now->gt($this->closed_at)) {
            return 'closed';
        }

        return 'open';
    }

    /**
     * 機能の日本語ステータスラベルを取得
     */
    public function getStatusLabel(?CarbonInterface $now = null): string
    {
        $status = $this->getStatus($now);

        return match ($status) {
            'disabled' => '停止中（手動）',
            'upcoming' => '開始前（'.($this->opened_at?->format('Y/m/d H:i') ?? '').' 開始）',
            'closed' => '受付終了（'.($this->closed_at?->format('Y/m/d H:i') ?? '').' 終了）',
            default => '受付中',
        };
    }

    /**
     * 停止中・期間外の案内メッセージを取得
     */
    public function getClosedMessage(?CarbonInterface $now = null): string
    {
        if (! empty($this->notice_message)) {
            return $this->notice_message;
        }

        $status = $this->getStatus($now);

        return match ($status) {
            'upcoming' => "「{$this->name}」は、".($this->opened_at?->format('Y年n月j日 H:i') ?? '').' より開始いたします。',
            'closed' => "「{$this->name}」は、".($this->closed_at?->format('Y年n月j日 H:i') ?? '').' をもって終了いたしました。',
            default => "現在、「{$this->name}」の受付を停止しております。本部スタッフまで直接お問い合わせください。",
        };
    }
}
