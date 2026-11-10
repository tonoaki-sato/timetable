<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentLogisticsController;
use App\Http\Controllers\EquipmentLogisticsPatternController;
use App\Http\Controllers\EquipmentLogisticsPatternStepController;
use App\Http\Controllers\EquipmentLogisticsPlanController;
use App\Http\Controllers\FeatureSettingController;
use App\Http\Controllers\LaneController;
use App\Http\Controllers\LaneRequestController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LocationScheduleController;
use App\Http\Controllers\LocationTypeController;
use App\Http\Controllers\ManualController;
use App\Http\Controllers\PreparationNavigatorController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffCsvController;
use App\Http\Controllers\StaffJoinController;
use App\Http\Controllers\StaffPrintController;
use App\Http\Controllers\StaffShiftController;
use App\Http\Controllers\StaffTimelineController;
use App\Http\Controllers\TimetableAssignmentWorkspaceController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TimetableMatrixController;
use App\Http\Controllers\TimetablePrintController;
use App\Http\Controllers\TimetableTaskAssignmentController;
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
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/my/shift/{token}', [StaffShiftController::class, 'show'])->name('staff.myshift');
    Route::post('/my/shift/{token}/checkin', [StaffShiftController::class, 'checkIn'])->name('staff.myshift.checkin');
    Route::post('/my/shift/{token}/assignments/{assignment}/status', [StaffShiftController::class, 'updateAssignmentStatus'])->name('staff.myshift.assignment-status');
    Route::post('/my/shift/{token}/tasks/{task}/status', [StaffShiftController::class, 'updateLaneTaskStatus'])->name('staff.myshift.lane-task-status');
    Route::post('/my/shift/{token}/lanes/{lane}/requests', [StaffShiftController::class, 'storeLaneRequest'])->name('staff.myshift.lane-requests.store');
});

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

    // スケジュール管理リソースルート
    Route::resource('schedules', ScheduleController::class);

    // 現場スタッフ（ボランティア）管理・印刷・CSVルート
    Route::get('staff/export-csv', [StaffCsvController::class, 'export'])->name('staff.export-csv');
    Route::post('staff/import-csv', [StaffCsvController::class, 'import'])->name('staff.import-csv');
    Route::get('staff/print-cards', [StaffPrintController::class, 'printCards'])->name('staff.print-cards');
    Route::get('staff/{staff}/print-card', [StaffPrintController::class, 'printSingleCard'])->name('staff.print-card');
    Route::post('staff/{staff}/regenerate-token', [StaffController::class, 'regenerateToken'])->name('staff.regenerate-token');
    Route::resource('staff', StaffController::class);

    // 汎用機能オン・オフ＆日時設定ルート
    Route::put('features/{featureKey}', [FeatureSettingController::class, 'update'])->name('feature-settings.update');
    Route::patch('features/{featureKey}/toggle', [FeatureSettingController::class, 'toggle'])->name('feature-settings.toggle');

    // タイムテーブル基本運行日リソースルート & 帳票印刷
    Route::get('timetables/{timetable}/assignments', [TimetableAssignmentWorkspaceController::class, 'index'])->name('timetables.assignments');
    Route::get('timetables/{timetable}/print-assignments', [TimetablePrintController::class, 'printAssignments'])->name('timetables.print-assignments');
    Route::get('timetables/{timetable}/print-matrix', [TimetablePrintController::class, 'printMatrix'])->name('timetables.print-matrix');
    Route::get('timetables/{timetable}/staff-timeline', [StaffTimelineController::class, 'index'])->name('timetables.staff-timeline');
    Route::get('timetables/{timetable}/tasks', [TimetableTaskController::class, 'indexByTimetable'])->name('timetables.tasks.index');
    Route::resource('timetables', TimetableController::class);

    // 担当班管理
    Route::get('/lanes', [LaneController::class, 'select'])->name('lanes.index');
    Route::scopeBindings()->group(function () {
        Route::get('timetables/{timetable}/lanes', [LaneController::class, 'index'])->name('timetables.lanes.index');
        Route::get('timetables/{timetable}/lanes/create', [LaneController::class, 'create'])->name('timetables.lanes.create');
        Route::post('timetables/{timetable}/lanes', [LaneController::class, 'store'])->name('timetables.lanes.store');
        Route::get('timetables/{timetable}/lanes/{lane}', [LaneController::class, 'show'])->name('timetables.lanes.show');
        Route::get('timetables/{timetable}/lanes/{lane}/edit', [LaneController::class, 'edit'])->name('timetables.lanes.edit');
        Route::put('timetables/{timetable}/lanes/{lane}', [LaneController::class, 'update'])->name('timetables.lanes.update');
        Route::delete('timetables/{timetable}/lanes/{lane}', [LaneController::class, 'destroy'])->name('timetables.lanes.destroy');
        Route::post('timetables/{timetable}/lanes/reorder', [LaneController::class, 'reorder'])->name('timetables.lanes.reorder');
    });
    Route::patch('lane-requests/{laneRequest}/status', [LaneRequestController::class, 'updateStatus'])->name('lane-requests.update-status');

    // 作業タスクリソースルート（モーダルによる登録・更新対応）
    Route::resource('timetable-tasks', TimetableTaskController::class)
        ->parameters(['timetable-tasks' => 'task'])
        ->only(['index', 'store', 'update', 'destroy']);

    // スタッフアサイン・出欠管理
    Route::get('timetable-tasks/{task}/assign-modal-data', [TimetableTaskAssignmentController::class, 'getAssignModalData'])->name('timetable-tasks.assign-modal-data');
    Route::post('timetable-tasks/{task}/assign', [TimetableTaskAssignmentController::class, 'assignStaff'])->name('timetable-tasks.assign');
    Route::post('timetable-tasks/{task}/assign-bulk', [TimetableTaskAssignmentController::class, 'bulkAssignStaff'])->name('timetable-tasks.assign-bulk');
    Route::post('timetable-tasks/{task}/assign-lane-pool', [TimetableTaskAssignmentController::class, 'assignLanePool'])->name('timetable-tasks.assign-lane-pool');
    Route::post('timetable-tasks/{task}/swap-staff', [TimetableTaskAssignmentController::class, 'swapStaff'])->name('timetable-tasks.swap-staff');
    Route::delete('timetable-tasks/{task}/staff/{staff}', [TimetableTaskAssignmentController::class, 'removeStaff'])->name('timetable-tasks.remove-staff');
    Route::patch('timetable-tasks/{task}/assignments/{assignment}', [TimetableTaskAssignmentController::class, 'updateAttendance'])->name('timetable-tasks.update-attendance');

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

    // 1. 資機材・備品管理 (Equipment Management)
    Route::post('equipments/reorder', [EquipmentController::class, 'reorder'])->name('equipments.reorder');
    Route::resource('equipments', EquipmentController::class);
    Route::group([], function () {
        Route::post('timetables/{timetable}/equipments/reorder', [EquipmentController::class, 'reorder'])->name('timetables.equipments.reorder');
        Route::get('timetables/{timetable}/equipments', [EquipmentController::class, 'timetableIndex'])->name('timetables.equipments.index');
        Route::get('timetables/{timetable}/equipments/create', [EquipmentController::class, 'timetableCreate'])->name('timetables.equipments.create');
        Route::post('timetables/{timetable}/equipments', [EquipmentController::class, 'timetableStore'])->name('timetables.equipments.store');
        Route::get('timetables/{timetable}/equipments/{equipment}', [EquipmentController::class, 'timetableShow'])->name('timetables.equipments.show');
        Route::get('timetables/{timetable}/equipments/{equipment}/edit', [EquipmentController::class, 'timetableEdit'])->name('timetables.equipments.edit');
        Route::put('timetables/{timetable}/equipments/{equipment}', [EquipmentController::class, 'timetableUpdate'])->name('timetables.equipments.update');
        Route::delete('timetables/{timetable}/equipments/{equipment}', [EquipmentController::class, 'timetableDestroy'])->name('timetables.equipments.destroy');
    });

    // 2. 搬出入計画・カスタムパターン管理 (Logistics & Operations)
    Route::get('/logistics', [EquipmentLogisticsController::class, 'select'])->name('logistics.index');
    Route::group([], function () {
        Route::get('timetables/{timetable}/logistics', [EquipmentLogisticsController::class, 'index'])->name('timetables.logistics.index');
        Route::get('timetables/{timetable}/logistics/print', [EquipmentLogisticsController::class, 'print'])->name('timetables.logistics.print');
        Route::get('timetables/{timetable}/logistics/equipments/{equipment}', [EquipmentLogisticsController::class, 'show'])->name('timetables.logistics.equipments.show');
        Route::post('timetables/{timetable}/logistics/{equipment}/apply-template', [EquipmentLogisticsPlanController::class, 'applyTemplate'])->name('timetables.logistics.apply-template');
        Route::delete('timetables/{timetable}/logistics/{equipment}/plans/clear', [EquipmentLogisticsPlanController::class, 'clear'])->name('timetables.logistics.plans.clear');

        // 搬出入計画ステップ操作
        Route::post('timetables/{timetable}/logistics/plans', [EquipmentLogisticsPlanController::class, 'store'])->name('timetables.logistics.plans.store');
        Route::put('timetables/{timetable}/logistics/plans/{plan}', [EquipmentLogisticsPlanController::class, 'update'])->name('timetables.logistics.plans.update');
        Route::delete('timetables/{timetable}/logistics/plans/{plan}', [EquipmentLogisticsPlanController::class, 'destroy'])->name('timetables.logistics.plans.destroy');
        Route::patch('timetables/{timetable}/logistics/plans/{plan}/status', [EquipmentLogisticsPlanController::class, 'updateStatus'])->name('timetables.logistics.plans.update-status');
    });

    // 搬出入パターンマスター管理 (Equipment Logistics Pattern Master)
    Route::resource('equipment-patterns', EquipmentLogisticsPatternController::class)
        ->parameters(['equipment-patterns' => 'pattern'])
        ->only(['index', 'store', 'update', 'destroy']);

    Route::post('equipment-patterns/{pattern}/steps', [EquipmentLogisticsPatternStepController::class, 'store'])->name('equipment-patterns.steps.store');
    Route::put('equipment-patterns/{pattern}/steps/{step}', [EquipmentLogisticsPatternStepController::class, 'update'])->name('equipment-patterns.steps.update');
    Route::delete('equipment-patterns/{pattern}/steps/{step}', [EquipmentLogisticsPatternStepController::class, 'destroy'])->name('equipment-patterns.steps.destroy');

    // 搬出入計画既存互換フォールバック
    Route::prefix('logistics')->name('logistics.')->group(function () {
        Route::get('print', [EquipmentLogisticsController::class, 'printLegacy'])->name('print');
        Route::get('equipments/{equipment}', [EquipmentLogisticsController::class, 'showLegacy'])->name('equipments.show');
        Route::post('{equipment}/apply-template', [EquipmentLogisticsPlanController::class, 'applyTemplateLegacy'])->name('apply-template');
        Route::post('{equipment}/save-as-pattern', [EquipmentLogisticsPatternController::class, 'saveFromEquipment'])->name('save-as-pattern');
        Route::delete('{equipment}/plans/clear', [EquipmentLogisticsPlanController::class, 'clearLegacy'])->name('plans.clear');

        Route::post('plans', [EquipmentLogisticsPlanController::class, 'storeLegacy'])->name('plans.store');
        Route::put('plans/{plan}', [EquipmentLogisticsPlanController::class, 'updateLegacy'])->name('plans.update');
        Route::delete('plans/{plan}', [EquipmentLogisticsPlanController::class, 'destroyLegacy'])->name('plans.destroy');
        Route::patch('plans/{plan}/status', [EquipmentLogisticsPlanController::class, 'updateStatusLegacy'])->name('plans.update-status');
    });

    // 拠点管理・時間帯スケジュール・拠点タイプ管理
    Route::scopeBindings()->group(function () {
        Route::get('timetables/{timetable}/locations', [LocationController::class, 'index'])->name('timetables.locations.index');
        Route::post('timetables/{timetable}/locations/reorder', [LocationController::class, 'reorder'])->name('timetables.locations.reorder');
        Route::get('timetables/{timetable}/locations/create', [LocationController::class, 'create'])->name('timetables.locations.create');
        Route::post('timetables/{timetable}/locations', [LocationController::class, 'store'])->name('timetables.locations.store');
        Route::get('timetables/{timetable}/locations/{location}', [LocationController::class, 'show'])->name('timetables.locations.show');
        Route::get('timetables/{timetable}/locations/{location}/edit', [LocationController::class, 'edit'])->name('timetables.locations.edit');
        Route::put('timetables/{timetable}/locations/{location}', [LocationController::class, 'update'])->name('timetables.locations.update');
        Route::delete('timetables/{timetable}/locations/{location}', [LocationController::class, 'destroy'])->name('timetables.locations.destroy');

        // 拠点スケジュール枠
        Route::post('timetables/{timetable}/locations/{location}/schedules', [LocationScheduleController::class, 'store'])->name('timetables.locations.schedules.store');
        Route::put('timetables/{timetable}/locations/{location}/schedules/{schedule}', [LocationScheduleController::class, 'update'])->name('timetables.locations.schedules.update');
        Route::patch('timetables/{timetable}/locations/{location}/schedules/{schedule}/status', [LocationScheduleController::class, 'updateStatus'])->name('timetables.locations.schedules.update-status');
        Route::delete('timetables/{timetable}/locations/{location}/schedules/{schedule}', [LocationScheduleController::class, 'destroy'])->name('timetables.locations.schedules.destroy');
    });

    // 拠点管理 既存互換フォールバック
    Route::get('locations/create', [LocationController::class, 'createRedirect'])->name('locations.create');
    Route::post('locations', [LocationController::class, 'storeLegacy'])->name('locations.store');
    Route::get('locations/{location}', [LocationController::class, 'showLegacy'])->name('locations.show');
    Route::get('locations/{location}/edit', [LocationController::class, 'editLegacy'])->name('locations.edit');
    Route::match(['put', 'patch'], 'locations/{location}', [LocationController::class, 'updateLegacy'])->name('locations.update');
    Route::delete('locations/{location}', [LocationController::class, 'destroyLegacy'])->name('locations.destroy');
    Route::post('locations/{location}/schedules', [LocationScheduleController::class, 'storeLegacy'])->name('locations.schedules.store');
    Route::put('locations/{location}/schedules/{schedule}', [LocationScheduleController::class, 'updateLegacy'])->name('locations.schedules.update');
    Route::patch('locations/{location}/schedules/{schedule}/status', [LocationScheduleController::class, 'updateStatusLegacy'])->name('locations.schedules.update-status');
    Route::delete('locations/{location}/schedules/{schedule}', [LocationScheduleController::class, 'destroyLegacy'])->name('locations.schedules.destroy');

    Route::resource('location-types', LocationTypeController::class)->except(['create', 'show', 'edit']);
});
