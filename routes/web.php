<?php

use App\Http\Controllers\AnnualScheduleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaneController;
use App\Http\Controllers\LaneRequestController;
use App\Http\Controllers\ManualController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\PreparationNavigatorController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffCsvController;
use App\Http\Controllers\StaffJoinController;
use App\Http\Controllers\StaffPrintController;
use App\Http\Controllers\StaffShiftController;
use App\Http\Controllers\StaffTimelineController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TimetableMatrixController;
use App\Http\Controllers\TimetablePrintController;
use App\Http\Controllers\TimetableTaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// 1. トップページ
Route::get('/', function () {
    return view('welcome');
})->name('home');

// 2. スタッフセルフ登録ルート (未ログインでアクセス可)
Route::get('/join', [StaffJoinController::class, 'create'])->name('staff.join');
Route::post('/join', [StaffJoinController::class, 'store'])->name('staff.join.store')->middleware('throttle:10,1');
Route::get('/join/completed', [StaffJoinController::class, 'completed'])->name('staff.join.completed');

// 3. 現場マイタイムテーブル閲覧 & チェックイン & 動態操作・班長要請ルート (トークン認証)
Route::get('/my/shift/{token}', [StaffShiftController::class, 'show'])->name('staff.myshift');
Route::post('/my/shift/{token}/checkin', [StaffShiftController::class, 'checkIn'])->name('staff.myshift.checkin');
Route::post('/my/shift/{token}/assignments/{assignment}/status', [StaffShiftController::class, 'updateAssignmentStatus'])->name('staff.myshift.assignment-status');
Route::post('/my/shift/{token}/tasks/{task}/status', [StaffShiftController::class, 'updateLaneTaskStatus'])->name('staff.myshift.lane-task-status');
Route::post('/my/shift/{token}/lanes/{lane}/requests', [StaffShiftController::class, 'storeLaneRequest'])->name('staff.myshift.lane-requests.store');

// 5. 認証ルート (未ログイン時のみアクセス可)
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
    Route::get('/dashboard/realtime', [DashboardController::class, 'realtime'])->name('dashboard.realtime');

    // 総合イベントカレンダー
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // ユーザー管理リソースルート
    Route::resource('users', UserController::class);

    // 年間スケジュール管理リソースルート
    Route::resource('annual-schedules', AnnualScheduleController::class);

    // 会議・打ち合わせ専用スケジュール管理リソースルート
    Route::resource('meetings', MeetingController::class);

    // 現場スタッフ（ボランティア）管理・印刷・CSVルート
    Route::get('staff/export-csv', [StaffCsvController::class, 'export'])->name('staff.export-csv');
    Route::post('staff/import-csv', [StaffCsvController::class, 'import'])->name('staff.import-csv');
    Route::get('staff/print-cards', [StaffPrintController::class, 'printCards'])->name('staff.print-cards');
    Route::get('staff/{staff}/print-card', [StaffPrintController::class, 'printSingleCard'])->name('staff.print-card');
    Route::post('staff/{staff}/regenerate-token', [StaffController::class, 'regenerateToken'])->name('staff.regenerate-token');
    Route::resource('staff', StaffController::class);

    // タイムテーブル基本運行日リソースルート & 帳票印刷
    Route::get('timetables/{timetable}/print-assignments', [TimetablePrintController::class, 'printAssignments'])->name('timetables.print-assignments');
    Route::get('timetables/{timetable}/staff-timeline', [StaffTimelineController::class, 'index'])->name('timetables.staff-timeline');
    Route::resource('timetables', TimetableController::class);

    // 担当班（レーン）管理
    Route::get('/lanes', [LaneController::class, 'select'])->name('lanes.index');
    Route::get('timetables/{timetable}/lanes', [LaneController::class, 'index'])->name('timetables.lanes.index');
    Route::post('timetables/{timetable}/lanes', [LaneController::class, 'store'])->name('timetables.lanes.store');
    Route::post('timetables/{timetable}/lanes/reorder', [LaneController::class, 'reorder'])->name('timetables.lanes.reorder');
    Route::put('timetables/{timetable}/lanes/{lane}', [LaneController::class, 'update'])->name('timetables.lanes.update');
    Route::patch('timetables/{timetable}/lanes/{lane}/lead-staff', [LaneController::class, 'updateLeadStaff'])->name('timetables.lanes.update-lead');
    Route::delete('timetables/{timetable}/lanes/{lane}', [LaneController::class, 'destroy'])->name('timetables.lanes.destroy');
    Route::patch('lane-requests/{laneRequest}/status', [LaneRequestController::class, 'updateStatus'])->name('lane-requests.update-status');

    // 作業タスクリソースルート
    Route::resource('timetable-tasks', TimetableTaskController::class)->parameters(['timetable-tasks' => 'task']);

    // スタッフアサイン・出欠管理
    Route::get('timetable-tasks/{task}/assign-modal-data', [TimetableTaskController::class, 'getAssignModalData'])->name('timetable-tasks.assign-modal-data');
    Route::post('timetable-tasks/{task}/assign', [TimetableTaskController::class, 'assignStaff'])->name('timetable-tasks.assign');
    Route::post('timetable-tasks/{task}/assign-bulk', [TimetableTaskController::class, 'bulkAssignStaff'])->name('timetable-tasks.assign-bulk');
    Route::post('timetable-tasks/{task}/assign-lane-pool', [TimetableTaskController::class, 'assignLanePool'])->name('timetable-tasks.assign-lane-pool');
    Route::post('timetable-tasks/{task}/swap-staff', [TimetableTaskController::class, 'swapStaff'])->name('timetable-tasks.swap-staff');
    Route::delete('timetable-tasks/{task}/staff/{staff}', [TimetableTaskController::class, 'removeStaff'])->name('timetable-tasks.remove-staff');
    Route::patch('timetable-tasks/{task}/assignments/{assignment}', [TimetableTaskController::class, 'updateAttendance'])->name('timetable-tasks.update-attendance');

    // 自身のプロフィールへのショートカット
    Route::get('/profile', function () {
        return redirect()->route('users.show', Auth::user());
    })->name('profile');

    // システム操作マニュアル
    Route::get('/manual', [ManualController::class, 'index'])->name('manual.index');

    // まつり運行準備ナビゲーター
    Route::get('/preparation-navigator', [PreparationNavigatorController::class, 'index'])->name('preparation-navigator.index');
    Route::get('/preparation-navigator/run', [PreparationNavigatorController::class, 'run'])->name('preparation-navigator.run');

    // 幹事会向けプレゼンテーションスライド
    Route::get('/presentation', [PresentationController::class, 'index'])->name('presentation.index');

    // タイムテーブル全体運行マトリクスビュー
    Route::get('/timetable', [TimetableMatrixController::class, 'index'])->name('timetable.index');
});
