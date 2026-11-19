<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticateUserAction
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * レート制限付きログイン認証を実行し、最終ログイン日時を更新
     *
     * @throws ValidationException
     */
    public function execute(string $email, string $password, bool $remember, ?string $ipAddress): User
    {
        $throttleKey = Str::transliterate(Str::lower($email).'|'.($ipAddress ?? ''));
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => [trans('auth.throttle', ['seconds' => $seconds])],
            ]);
        }

        if (Auth::attempt([
            'email' => $email,
            'password' => $password,
            'is_active' => true,
        ], $remember)) {
            RateLimiter::clear($throttleKey);

            $user = Auth::user();
            if ($user instanceof User) {
                $this->userRepository->update($user, ['last_login_at' => now()]);

                return $user;
            }
        }

        RateLimiter::hit($throttleKey);

        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }
}
