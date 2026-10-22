<?php

use App\Http\Controllers\AnnualScheduleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaneController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TimetableMatrixController;
use App\Http\Controllers\TimetableTaskController;
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
    Route::patch('annual-schedules/{annual_schedule}/reschedule', [AnnualScheduleController::class, 'reschedule'])->name('annual-schedules.reschedule');
    Route::resource('annual-schedules', AnnualScheduleController::class);

    // 会議・打ち合わせ専用スケジュール管理リソースルート
    Route::patch('meetings/{meeting}/reschedule', [MeetingController::class, 'reschedule'])->name('meetings.reschedule');
    Route::resource('meetings', MeetingController::class);

    // 現場スタッフ（ボランティア）管理リソースルート
    Route::resource('staff', StaffController::class);

    // タイムテーブル基本運行日リソースルート
    Route::resource('timetables', TimetableController::class);

    // 担当班（レーン）管理
    Route::get('timetables/{timetable}/lanes', [LaneController::class, 'index'])->name('timetables.lanes.index');
    Route::post('timetables/{timetable}/lanes', [LaneController::class, 'store'])->name('timetables.lanes.store');
    Route::put('timetables/{timetable}/lanes/{lane}', [LaneController::class, 'update'])->name('timetables.lanes.update');
    Route::patch('timetables/{timetable}/lanes/{lane}/lead-staff', [LaneController::class, 'updateLeadStaff'])->name('timetables.lanes.update-lead');
    Route::delete('timetables/{timetable}/lanes/{lane}', [LaneController::class, 'destroy'])->name('timetables.lanes.destroy');

    // 作業タスクリソースルート
    Route::resource('timetable-tasks', TimetableTaskController::class)->parameters(['timetable-tasks' => 'task']);

    // スタッフアサイン・出欠管理
    Route::post('timetable-tasks/{task}/assign', [TimetableTaskController::class, 'assignStaff'])->name('timetable-tasks.assign');
    Route::delete('timetable-tasks/{task}/staff/{staff}', [TimetableTaskController::class, 'removeStaff'])->name('timetable-tasks.remove-staff');
    Route::patch('timetable-tasks/{task}/assignments/{assignment}', [TimetableTaskController::class, 'updateAttendance'])->name('timetable-tasks.update-attendance');

    // 自身のプロフィールへのショートカット
    Route::get('/profile', function () {
        return redirect()->route('users.show', Auth::user());
    })->name('profile');

    // タイムテーブル全体運行マトリクスビュー
    Route::get('/timetable', [TimetableMatrixController::class, 'index'])->name('timetable.index');
});
