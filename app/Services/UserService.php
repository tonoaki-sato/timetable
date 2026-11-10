<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * ユーザー一覧の取得（検索・絞り込み・ページネーション）
     */
    public function paginateFiltered(
        ?string $search = null,
        ?string $role = null,
        ?bool $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->userRepository->paginateFiltered($search, $role, $status, $perPage);
    }

    /**
     * ユーザーの新規登録
     *
     * @param  array<string, mixed>  $validated
     */
    public function createUser(array $validated, bool $isActive = true): User
    {
        return $this->userRepository->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make((string) $validated['password']),
            'role' => $validated['role'],
            'is_active' => $isActive,
        ]);
    }

    /**
     * ユーザー情報の更新（自分自身の権限降格・無効化防止を含む）
     *
     * @param  array<string, mixed>  $validated
     */
    public function updateUser(
        User $targetUser,
        User $actorUser,
        array $validated,
        bool $hasPassword,
        bool $isActive
    ): bool {
        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($hasPassword && ! empty($validated['password'])) {
            $data['password'] = Hash::make((string) $validated['password']);
        }

        if ($actorUser->isAdmin()) {
            if ($targetUser->id === $actorUser->id) {
                $data['role'] = 'admin';
                $data['is_active'] = true;
            } else {
                $data['role'] = $validated['role'];
                $data['is_active'] = $isActive;
            }
        }

        return $this->userRepository->update($targetUser, $data);
    }

    /**
     * ユーザーの削除
     */
    public function deleteUser(User $user): bool
    {
        return $this->userRepository->delete($user);
    }
}
