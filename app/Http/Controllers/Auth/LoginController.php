<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * ログインフォーム画面の表示
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * ログイン認証処理
     */
    public function login(Request $request): RedirectResponse
    {
        // 1. 入力バリデーション
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // レートリミット判定（メールアドレス+IP単位、1分間に5回まで）
        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => [trans('auth.throttle', ['seconds' => $seconds])],
            ]);
        }

        $remember = $request->boolean('remember');

        // 2. 認証試行 (有効アカウント is_active = true のみ許可)
        if (Auth::attempt(array_merge($credentials, ['is_active' => true]), $remember)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            // 最終ログイン日時の更新 (U-101 連携)
            $user = Auth::user();
            if ($user instanceof User) {
                $user->last_login_at = now();
                $user->save();
            }

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($throttleKey);

        // 3. 認証失敗時（アカウント非存在、誤パスワード、停止中アカウントすべて同一メッセージ）
        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    /**
     * ログアウト処理
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'ログアウトしました。');
    }
}
