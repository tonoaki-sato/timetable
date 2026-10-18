<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $remember = $request->boolean('remember');

        // 2. 認証試行 (有効アカウント is_active = true のみ許可)
        if (Auth::attempt(array_merge($credentials, ['is_active' => true]), $remember)) {
            $request->session()->regenerate();

            // 最終ログイン日時の更新 (U-101 連携)
            $user = Auth::user();
            if ($user) {
                $user->last_login_at = now();
                $user->save();
            }

            return redirect()->intended(route('dashboard'));
        }

        // 3. アカウントが無効化 (is_active = false) されている場合の個別判定
        if (Auth::validate($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['このアカウントは現在停止されています。システム管理者に確認してください。'],
            ]);
        }

        // 4. 認証失敗時
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
