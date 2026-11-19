<?php

namespace App\Http\Controllers;

use App\Actions\Schedule\DownloadSchedulesPdfAction;
use App\Actions\Schedule\ExportScheduleIcsAction;
use App\DTOs\ScheduleFilterDTO;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Models\Schedule;
use App\Services\ScheduleService;
use DomainException;
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

        $filter = ScheduleFilterDTO::fromArray($request->query());

        return view('schedules.index', $this->scheduleService->getIndexViewDataByFilter($filter));
    }

    /**
     * 年間スケジュール一覧 A4縦PDFのダウンロード
     */
    public function pdf(Request $request): Response
    {
        $this->authorize('viewAny', Schedule::class);

        $filter = ScheduleFilterDTO::fromArray($request->query());

        return $this->downloadSchedulesPdfAction->execute(
            category: $filter->category,
            status: $filter->status,
            search: $filter->search,
            staffId: $filter->staffId,
            tab: $filter->tab
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

        $filter = ScheduleFilterDTO::fromArray($request->query());
        $schedules = $this->scheduleService->getFilteredForIcsByFilter($filter);

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
                'schedule' => $this->scheduleService->loadAssignee($schedule),
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

        return view('schedules.show', [
            'schedule' => $this->scheduleService->getScheduleDetails($schedule),
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

        try {
            $this->scheduleService->deleteSchedule($schedule);
        } catch (DomainException $e) {
            return redirect()->route('schedules.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('schedules.index')
            ->with('success', "予定「{$title}」を削除しました。");
    }
}
