<?php

use App\Http\Controllers\AnnualScheduleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// 1. トップページ
Route::get('/', function () {
    return view('welcome');
})->name('home');

// 2. 認証ルート (未ログイン時のみアクセス可)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

// 3. ログアウトルート (ログイン時のみ)
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// 4. 認証必須ルート (ログイン時のみ)
Route::middleware('auth')->group(function () {
    // 本部ダッシュボード
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ユーザー管理リソースルート
    Route::resource('users', UserController::class);

    // 年間スケジュール管理リソースルート
    Route::resource('annual-schedules', AnnualScheduleController::class);

    // 自身のプロフィールへのショートカット
    Route::get('/profile', function () {
        return redirect()->route('users.show', Auth::user());
    })->name('profile');

    // タイムテーブル（未実装プレースホルダー）
    Route::get('/timetable', function () {
        return redirect()->route('dashboard');
    })->name('timetable.index');
});
