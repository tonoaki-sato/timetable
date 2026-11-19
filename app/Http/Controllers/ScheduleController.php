<?php

namespace App\Http\Controllers;

use App\Actions\Schedule\DownloadSchedulesPdfAction;
use App\Actions\Schedule\ExportScheduleIcsAction;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Models\Schedule;
use App\Services\ScheduleService;
use App\Support\TableQueryOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(
        protected ScheduleService $scheduleService,
        protected DownloadSchedulesPdfAction $downloadSchedulesPdfAction
    ) {}

    /**
     * スケジュール一覧画面の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Schedule::class);

        $category = $request->input('category');
        $status = $request->input('status');
        $search = $request->input('search');
        $staffId = $request->filled('staff_id') ? (int) $request->input('staff_id') : null;
        $tab = $request->input('tab', 'upcoming');
        $perPage = TableQueryOptions::resolvePerPage($request->query('per_page'), 100, [25, 50, 100]);
        $sort = $request->filled('sort') ? (string) $request->query('sort') : null;
        $direction = $request->filled('direction') ? (string) $request->query('direction') : null;

        $viewData = $this->scheduleService->getIndexViewData(
            category: $category,
            status: $status,
            search: $search,
            staffId: $staffId,
            tab: $tab,
            perPage: $perPage,
            sort: $sort,
            direction: $direction,
        );

        return view('schedules.index', $viewData);
    }

    /**
     * 年間スケジュール一覧 A4縦PDFのダウンロード
     */
    public function pdf(Request $request): Response
    {
        $this->authorize('viewAny', Schedule::class);

        $category = $request->input('category');
        $status = $request->input('status');
        $search = $request->input('search');
        $staffId = $request->filled('staff_id') ? (int) $request->input('staff_id') : null;
        $tab = $request->input('tab', 'upcoming');

        return $this->downloadSchedulesPdfAction->execute(
            category: $category,
            status: $status,
            search: $search,
            staffId: $staffId,
            tab: $tab
        );
    }

    /**
     * 単一スケジュールの .ics (iCalendar) エクスポート
     */
    public function exportIcs(Schedule $schedule, ExportScheduleIcsAction $exportIcsAction): Response
    {
        $this->authorize('view', $schedule);

        return $exportIcsAction->executeSingle($schedule);
    }

    /**
     * 絞り込み条件に基づくスケジュール一覧の .ics (iCalendar) エクスポート
     */
    public function exportAllIcs(Request $request, ExportScheduleIcsAction $exportIcsAction): Response
    {
        $this->authorize('viewAny', Schedule::class);

        $schedules = $this->scheduleService->getFilteredForIcs(
            category: $request->input('category'),
            status: $request->input('status'),
            search: $request->input('search'),
            staffId: $request->filled('staff_id') ? (int) $request->input('staff_id') : null,
            tab: $request->input('tab', 'upcoming')
        );

        return $exportIcsAction->executeCollection($schedules);
    }

    /**
     * スケジュール新規登録画面の表示
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Schedule::class);

        return view('schedules.create', [
            'categories' => Schedule::CATEGORIES,
            'statuses' => Schedule::STATUSES,
            'staffs' => $this->scheduleService->getActiveStaffList(),
            'defaultCategory' => $request->input('category', 'other'),
            'defaultDate' => $request->input('date', now()->toDateString()),
        ]);
    }

    /**
     * スケジュール新規登録処理
     */
    public function store(StoreScheduleRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Schedule::class);

        $schedule = $this->scheduleService->createSchedule(
            $request->validated(),
            $request->user()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "予定「{$schedule->title}」を登録しました。",
                'schedule' => $schedule->load('assignee'),
            ], 201);
        }

        return redirect()->route('schedules.show', $schedule)
            ->with('success', "予定「{$schedule->title}」を登録しました。");
    }

    /**
     * スケジュール詳細画面の表示
     */
    public function show(Schedule $schedule): View
    {
        $this->authorize('view', $schedule);

        $schedule->load(['creator', 'assignee', 'timetable']);

        return view('schedules.show', [
            'schedule' => $schedule,
        ]);
    }

    /**
     * スケジュール編集画面の表示
     */
    public function edit(Schedule $schedule): View
    {
        $this->authorize('update', $schedule);

        return view('schedules.edit', [
            'schedule' => $schedule,
            'categories' => Schedule::CATEGORIES,
            'statuses' => Schedule::STATUSES,
            'staffs' => $this->scheduleService->getActiveStaffList(),
        ]);
    }

    /**
     * スケジュール更新処理
     */
    public function update(UpdateScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        $this->authorize('update', $schedule);

        $this->scheduleService->updateSchedule($schedule, $request->validated());

        return redirect()->route('schedules.show', $schedule)
            ->with('success', "予定「{$schedule->title}」を更新しました。");
    }

    /**
     * スケジュール削除処理
     */
    public function destroy(Schedule $schedule): RedirectResponse
    {
        $this->authorize('delete', $schedule);

        $title = $schedule->title;

        if ($schedule->is_linked_to_timetable) {
            return redirect()->route('schedules.index')
                ->with('error', "予定「{$title}」は運行スケジュール（タイムテーブル）と自動連動しているため、スケジュール管理画面からは削除できません。運行情報管理画面から削除してください。");
        }

        $this->scheduleService->deleteSchedule($schedule);

        return redirect()->route('schedules.index')
            ->with('success', "予定「{$title}」を削除しました。");
    }
}
