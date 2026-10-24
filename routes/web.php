<?php

use App\Http\Controllers\AnnualScheduleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaneController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffCsvController;
use App\Http\Controllers\StaffJoinController;
use App\Http\Controllers\StaffPrintController;
use App\Http\Controllers\StaffShiftController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TimetableMatrixController;
use App\Http\Controllers\TimetablePrintController;
use App\Http\Controllers\TimetableTaskController;
use App\Http\Controllers\UserController;
use App\Models\Timetable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// 1. トップページ
Route::get('/', function () {
    return view('welcome');
})->name('home');

// 2. スタッフセルフ登録ルート (未ログインでアクセス可)
Route::get('/join', [StaffJoinController::class, 'create'])->name('staff.join');
Route::post('/join', [StaffJoinController::class, 'store'])->name('staff.join.store');
Route::get('/join/completed', [StaffJoinController::class, 'completed'])->name('staff.join.completed');

// 3. 現場マイタイムテーブル閲覧 & チェックイン & 動態操作ルート (トークン認証)
Route::get('/my/shift/{token}', [StaffShiftController::class, 'show'])->name('staff.myshift');
Route::post('/my/shift/{token}/checkin', [StaffShiftController::class, 'checkIn'])->name('staff.myshift.checkin');
Route::post('/my/shift/{token}/line-bind', [StaffShiftController::class, 'bindLine'])->name('staff.myshift.line-bind');
Route::post('/my/shift/{token}/assignments/{assignment}/status', [StaffShiftController::class, 'updateAssignmentStatus'])->name('staff.myshift.assignment-status');

// 4. URL紛失時のスタッフ照合ルート
Route::get('/my/find', [StaffShiftController::class, 'showFindForm'])->name('staff.myshift.find');
Route::post('/my/find', [StaffShiftController::class, 'find'])->name('staff.myshift.find.post');

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
    Route::resource('timetables', TimetableController::class);

    // 担当班（レーン）管理
    Route::get('/lanes', function () {
        $timetable = Timetable::where('status', 'active')->first()
            ?? Timetable::orderBy('target_date')->first();

        if (! $timetable) {
            return redirect()->route('timetables.index')
                ->with('info', '担当班を編成する前に、運行日を登録してください。');
        }

        return redirect()->route('timetables.lanes.index', $timetable);
    })->name('lanes.index');
    Route::get('timetables/{timetable}/lanes', [LaneController::class, 'index'])->name('timetables.lanes.index');
    Route::post('timetables/{timetable}/lanes', [LaneController::class, 'store'])->name('timetables.lanes.store');
    Route::post('timetables/{timetable}/lanes/reorder', [LaneController::class, 'reorder'])->name('timetables.lanes.reorder');
    Route::post('timetables/{timetable}/lanes/copy-from', [LaneController::class, 'copyFrom'])->name('timetables.lanes.copy-from');
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
