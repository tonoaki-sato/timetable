<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    public function login(LoginRequest $request, AuthenticateUserAction $authenticateUserAction): RedirectResponse
    {
        $validated = $request->validated();

        $authenticateUserAction->execute(
            email: (string) $validated['email'],
            password: (string) $validated['password'],
            remember: $request->boolean('remember'),
            ipAddress: $request->ip(),
        );

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
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
