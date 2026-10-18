<?php

namespace App\Models;

use Database\Factories\AnnualScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'target_year',
    'title',
    'category',
    'start_date',
    'end_date',
    'location',
    'status',
    'description',
    'created_by',
])]
class AnnualSchedule extends Model
{
    /** @use HasFactory<AnnualScheduleFactory> */
    use HasFactory;

    /**
     * カテゴリ定義一覧（キー => 日本語ラベル）
     *
     * @var array<string, string>
     */
    public const array CATEGORIES = [
        'committee' => '会議・委員会',
        'permit' => '申請・協議',
        'briefing' => '募集・説明会',
        'operation' => '運行・設営',
        'ceremony' => '式典・神事',
        'other' => 'その他',
    ];

    /**
     * ステータス定義一覧（キー => 日本語ラベル）
     *
     * @var array<string, string>
     */
    public const array STATUSES = [
        'planned' => '予定',
        'in_progress' => '進行中',
        'completed' => '完了',
        'cancelled' => '中止',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * 作成者ユーザーとのリレーション
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * カテゴリの日本語表示
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * ステータスの日本語表示
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * 対象年度によるスコープ
     */
    public function scopeYear(Builder $query, ?int $year): Builder
    {
        if ($year) {
            return $query->where('target_year', $year);
        }

        return $query;
    }

    /**
     * カテゴリによるスコープ
     */
    public function scopeCategory(Builder $query, ?string $category): Builder
    {
        if ($category) {
            return $query->where('category', $category);
        }

        return $query;
    }

    /**
     * ステータスによるスコープ
     */
    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if ($status) {
            return $query->where('status', $status);
        }

        return $query;
    }

    /**
     * キーワード検索スコープ（タイトル・場所・詳細）
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search) {
            return $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
