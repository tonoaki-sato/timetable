<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\TableQueryOptions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Override;

class UserRepository implements UserRepositoryInterface
{
    #[Override]
    public function paginateFiltered(
        ?string $search = null,
        ?string $role = null,
        ?bool $status = null,
        int $perPage = 15,
        ?string $sort = null,
        ?string $direction = null,
    ): LengthAwarePaginator {
        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($status !== null) {
            $query->where('is_active', $status);
        }

        $resolved = TableQueryOptions::resolveSort(
            $sort,
            $direction,
            ['id', 'name', 'email', 'role', 'is_active', 'last_login_at'],
            'id',
            'desc',
        );

        return $query->orderBy($resolved['sort'], $resolved['direction'])
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    #[Override]
    public function countActiveAdmins(): int
    {
        return User::where('role', 'admin')->where('is_active', true)->count();
    }

    #[Override]
    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    #[Override]
    public function update(User $user, array $attributes): bool
    {
        return $user->update($attributes);
    }

    #[Override]
    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }
}
