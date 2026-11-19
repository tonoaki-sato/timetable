<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * 権限ロール Enum
     */
    public function getRoleEnumAttribute(): ?UserRole
    {
        return UserRole::tryFrom((string) $this->role);
    }

    /**
     * 作成したタイムテーブル一覧とのリレーション
     */
    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class, 'created_by');
    }

    /**
     * 登録したスケジュール一覧とのリレーション
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'created_by');
    }

    /**
     * 対応した班長応援・機材申請一覧とのリレーション
     */
    public function resolvedLaneRequests(): HasMany
    {
        return $this->hasMany(LaneRequest::class, 'resolved_by_user_id');
    }

    /**
     * システム管理者かどうかの判定
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin->value;
    }

    /**
     * 本部オペレーターかどうかの判定
     */
    public function isOperator(): bool
    {
        return $this->role === UserRole::Operator->value;
    }
}
