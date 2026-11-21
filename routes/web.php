<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentCsvController;
use App\Http\Controllers\EquipmentLogisticsController;
use App\Http\Controllers\EquipmentLogisticsPatternController;
use App\Http\Controllers\EquipmentLogisticsPatternStepController;
use App\Http\Controllers\EquipmentLogisticsPlanController;
use App\Http\Controllers\EquipmentLogisticsTimelineController;
use App\Http\Controllers\FeatureSettingController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LaneController;
use App\Http\Controllers\LanePrintController;
use App\Http\Controllers\LaneRequestController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LocationTypeController;
use App\Http\Controllers\ManualController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PreparationNavigatorController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SiteBaseMapController;
use App\Http\Controllers\SiteLayoutController;
use App\Http\Controllers\SiteMapController;
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
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorCsvController;
use Illuminate\Support\Facades\Route;

// 1. トップページ
Route::get('/', HomeController::class)->name('home');

// 2. スタッフセルフ登録ルート (未ログインでアクセス可)
Route::get('/join', [StaffJoinController::class, 'create'])->name('staff.join');
Route::post('/join', [StaffJoinController::class, 'store'])->name('staff.join.store')->middleware('throttle:10,1');
Route::get('/join/completed', [StaffJoinController::class, 'completed'])->name('staff.join.completed');

// 3. 現場マイタイムテーブル閲覧 & チェックイン & 動態操作・班長要請ルート (トークン認証)
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/my/shift/{token}', [StaffShiftController::class, 'show'])->name('staff.myshift');
    Route::get('/my/shift/{token}/ics', [StaffShiftController::class, 'exportIcs'])->name('staff.myshift.ics');
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

    // グローバル横断クイック検索API
    Route::get('/search/quick', GlobalSearchController::class)->middleware('throttle:60,1')->name('search.quick');

    // 総合イベントカレンダー
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // ユーザー管理リソースルート
    Route::resource('users', UserController::class);

    // スケジュール管理リソースルート
    Route::get('schedules/pdf', [ScheduleController::class, 'pdf'])->name('schedules.pdf');
    Route::get('schedules/ics', [ScheduleController::class, 'exportAllIcs'])->name('schedules.ics.all');
    Route::get('schedules/{schedule}/ics', [ScheduleController::class, 'exportIcs'])->name('schedules.ics');
    Route::resource('schedules', ScheduleController::class);

    // 現場スタッフ（ボランティア）管理・印刷・CSVルート
    Route::get('staff/export-csv', [StaffCsvController::class, 'export'])->name('staff.export-csv');
    Route::get('staff/print-cards', [StaffPrintController::class, 'printCards'])->name('staff.print-cards');
    Route::get('staff/{staff}/print-card', [StaffPrintController::class, 'printSingleCard'])->name('staff.print-card');
    Route::get('staff/pdf', [StaffController::class, 'pdf'])->name('staff.pdf');
    Route::post('staff/{staff}/regenerate-token', [StaffController::class, 'regenerateToken'])->name('staff.regenerate-token');
    Route::resource('staff', StaffController::class);

    // 組織管理 (Organization Management)
    Route::post('organizations/reorder', [OrganizationController::class, 'reorder'])->name('organizations.reorder');
    Route::get('organizations/print', [OrganizationController::class, 'print'])->name('organizations.print');
    Route::get('organizations/staff-list-pdf', [OrganizationController::class, 'staffListPdf'])->name('organizations.staff-list-pdf');
    Route::resource('organizations', OrganizationController::class)->except(['create']);

    // 汎用機能オン・オフ＆日時設定ルート
    Route::put('features/{featureKey}', [FeatureSettingController::class, 'update'])->name('feature-settings.update');
    Route::patch('features/{featureKey}/toggle', [FeatureSettingController::class, 'toggle'])->name('feature-settings.toggle');

    // タイムテーブル基本運行日リソースルート & 帳票印刷
    Route::get('/assignments', [TimetableAssignmentWorkspaceController::class, 'redirect'])->name('assignments.index');
    Route::get('timetables/{timetable}/assignments', [TimetableAssignmentWorkspaceController::class, 'index'])->name('timetables.assignments');
    Route::get('timetables/{timetable}/print-assignments', [TimetablePrintController::class, 'printAssignments'])->name('timetables.print-assignments');
    Route::get('timetables/{timetable}/print-matrix', [TimetablePrintController::class, 'printMatrix'])->name('timetables.print-matrix');
    Route::get('timetables/{timetable}/staff-timeline', [StaffTimelineController::class, 'index'])->name('timetables.staff-timeline');
    Route::get('timetables/{timetable}/tasks', [TimetableTaskController::class, 'indexByTimetable'])->name('timetables.tasks.index');
    Route::resource('timetables', TimetableController::class)->except(['create', 'edit']);

    // 担当班管理
    Route::get('/lanes', [LaneController::class, 'redirect'])->name('lanes.index');
    Route::scopeBindings()->group(function () {
        Route::get('timetables/{timetable}/lanes', [LaneController::class, 'index'])->name('timetables.lanes.index');
        Route::get('timetables/{timetable}/lanes/create', [LaneController::class, 'create'])->name('timetables.lanes.create');
        Route::get('timetables/{timetable}/lanes/print', [LanePrintController::class, 'print'])->name('timetables.lanes.print');
        Route::post('timetables/{timetable}/lanes', [LaneController::class, 'store'])->name('timetables.lanes.store');
        Route::get('timetables/{timetable}/lanes/{lane}', [LaneController::class, 'show'])->name('timetables.lanes.show');
        Route::get('timetables/{timetable}/lanes/{lane}/edit', [LaneController::class, 'edit'])->name('timetables.lanes.edit');
        Route::put('timetables/{timetable}/lanes/{lane}', [LaneController::class, 'update'])->name('timetables.lanes.update');
        Route::delete('timetables/{timetable}/lanes/{lane}', [LaneController::class, 'destroy'])->name('timetables.lanes.destroy');
        Route::post('timetables/{timetable}/lanes/reorder', [LaneController::class, 'reorder'])->name('timetables.lanes.reorder');
    });
    Route::patch('lane-requests/{laneRequest}/status', [LaneRequestController::class, 'updateStatus'])->name('lane-requests.update-status');

    // 作業タスクリソースルート（モーダルによる登録・更新対応）
    Route::post('timetable-tasks/common', [TimetableTaskController::class, 'storeCommon'])->name('timetable-tasks.common.store');
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
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');

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
    Route::get('equipments/export-csv', [EquipmentCsvController::class, 'export'])->name('equipments.export-csv');
    Route::post('equipments/import-csv', [EquipmentCsvController::class, 'import'])->name('equipments.import-csv');
    Route::post('equipments/reorder', [EquipmentController::class, 'reorder'])->name('equipments.reorder');
    Route::resource('equipments', EquipmentController::class)->except(['create', 'show', 'edit']);

    // 搬出入タイムライン (Logistics Timeline)
    Route::get('/logistics-timeline', [EquipmentLogisticsTimelineController::class, 'redirect'])->name('logistics-timeline.index');
    Route::get('timetables/{timetable}/logistics-timeline', [EquipmentLogisticsTimelineController::class, 'index'])->name('timetables.logistics-timeline');

    // 2. 搬出入計画・カスタムパターン管理 (Logistics & Operations)
    Route::get('/logistics', [EquipmentLogisticsController::class, 'redirect'])->name('logistics.index');
    Route::get('timetables/{timetable}/logistics', [EquipmentLogisticsController::class, 'index'])->name('timetables.logistics.index');
    Route::get('timetables/{timetable}/logistics/print', [EquipmentLogisticsController::class, 'print'])->name('timetables.logistics.print');
    Route::post('timetables/{timetable}/logistics/{equipment}/apply-template', [EquipmentLogisticsPlanController::class, 'applyTemplate'])->name('timetables.logistics.apply-template');

    // 搬出入計画ステップ操作
    Route::post('timetables/{timetable}/logistics/plans', [EquipmentLogisticsPlanController::class, 'store'])->name('timetables.logistics.plans.store');
    Route::put('timetables/{timetable}/logistics/plans/{plan}', [EquipmentLogisticsPlanController::class, 'update'])->name('timetables.logistics.plans.update');
    Route::delete('timetables/{timetable}/logistics/plans/{plan}', [EquipmentLogisticsPlanController::class, 'destroy'])->name('timetables.logistics.plans.destroy');

    // 搬出入パターンマスター管理 (Equipment Logistics Pattern Master)
    Route::resource('equipment-patterns', EquipmentLogisticsPatternController::class)
        ->parameters(['equipment-patterns' => 'pattern'])
        ->only(['index', 'store', 'update', 'destroy']);

    Route::post('equipment-patterns/{pattern}/steps', [EquipmentLogisticsPatternStepController::class, 'store'])->name('equipment-patterns.steps.store');
    Route::post('equipment-patterns/{pattern}/steps/reorder', [EquipmentLogisticsPatternStepController::class, 'reorder'])->name('equipment-patterns.steps.reorder');
    Route::scopeBindings()->group(function () {
        Route::put('equipment-patterns/{pattern}/steps/{step}', [EquipmentLogisticsPatternStepController::class, 'update'])->name('equipment-patterns.steps.update');
        Route::delete('equipment-patterns/{pattern}/steps/{step}', [EquipmentLogisticsPatternStepController::class, 'destroy'])->name('equipment-patterns.steps.destroy');
    });

    // 拠点マスター管理 (Locations)
    Route::post('locations/reorder', [LocationController::class, 'reorder'])->name('locations.reorder');
    Route::resource('locations', LocationController::class)->except(['create', 'show', 'edit']);

    Route::scopeBindings()->group(function () {
        // 現場配置計画 (Site Layout)
        Route::get('timetables/{timetable}/layouts', [SiteLayoutController::class, 'index'])->name('timetables.layouts.index');
        Route::get('timetables/{timetable}/layouts/booths', [SiteLayoutController::class, 'booths'])->name('timetables.layouts.booths');
        Route::post('timetables/{timetable}/layouts/booths', [SiteLayoutController::class, 'storeBooth'])->name('timetables.layouts.booths.store');
        Route::put('timetables/{timetable}/layouts/booths/{item}', [SiteLayoutController::class, 'updateBooth'])->name('timetables.layouts.booths.update');
        Route::delete('timetables/{timetable}/layouts/booths/{item}', [SiteLayoutController::class, 'destroyBooth'])->name('timetables.layouts.booths.destroy');

        // 白地図マッピングエディタ (Site Map Editor)
        Route::get('timetables/{timetable}/site-maps/editor', [SiteMapController::class, 'editor'])->name('timetables.site-maps.editor');
        Route::patch('timetables/{timetable}/site-maps/base-map', [SiteMapController::class, 'updateBaseMap'])->name('timetables.site-maps.base-map.update');
        Route::get('timetables/{timetable}/site-maps/pdf', [SiteMapController::class, 'exportPdf'])->name('timetables.site-maps.pdf');
        Route::post('timetables/{timetable}/site-maps/reset-preset', [SiteMapController::class, 'resetPreset'])->name('timetables.site-maps.reset-preset');
        Route::post('timetables/{timetable}/site-maps/clear-placements', [SiteMapController::class, 'clearPlacements'])->name('timetables.site-maps.clear-placements');
        Route::get('timetables/{timetable}/site-maps/placements', [SiteMapController::class, 'getPlacements'])->name('timetables.site-maps.placements.index');
        Route::post('timetables/{timetable}/site-maps/placements', [SiteMapController::class, 'storePlacement'])->name('timetables.site-maps.placements.store');
        Route::put('timetables/{timetable}/site-maps/placements-bulk', [SiteMapController::class, 'bulkUpdatePlacements'])->name('timetables.site-maps.placements.bulk-update');
        Route::put('timetables/{timetable}/site-maps/placements/{placement}', [SiteMapController::class, 'updatePlacement'])->name('timetables.site-maps.placements.update');
        Route::delete('timetables/{timetable}/site-maps/placements/{placement}', [SiteMapController::class, 'destroyPlacement'])->name('timetables.site-maps.placements.destroy');
        Route::post('timetables/{timetable}/site-maps/icons', [SiteMapController::class, 'storeIcon'])->name('timetables.site-maps.icons.store');
        Route::match(['post', 'put'], 'timetables/{timetable}/site-maps/icons/{iconDefinition}', [SiteMapController::class, 'updateIcon'])->name('timetables.site-maps.icons.update')->withoutScopedBindings();
        Route::delete('timetables/{timetable}/site-maps/icons/{iconDefinition}', [SiteMapController::class, 'destroyIcon'])->name('timetables.site-maps.icons.destroy')->withoutScopedBindings();
        Route::get('timetables/{timetable}/site-maps/icons/{iconDefinition}/usage', [SiteMapController::class, 'getIconUsage'])->name('timetables.site-maps.icons.usage')->withoutScopedBindings();
    });

    // 出展・出店者マスター管理 (Vendors)
    Route::get('vendors/export-csv', [VendorCsvController::class, 'export'])->name('vendors.export-csv');
    Route::post('vendors/import-csv', [VendorCsvController::class, 'import'])->name('vendors.import-csv');
    Route::resource('vendors', VendorController::class)->except(['create', 'show', 'edit']);

    Route::resource('location-types', LocationTypeController::class)->except(['create', 'show', 'edit']);

    // 白地図マスター管理 (Site Base Maps)
    Route::resource('site-base-maps', SiteBaseMapController::class)->except(['create', 'show', 'edit']);
});
