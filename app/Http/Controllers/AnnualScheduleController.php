<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnualScheduleRequest;
use App\Http\Requests\UpdateAnnualScheduleRequest;
use App\Models\AnnualSchedule;
use App\Models\Staff;
use App\Services\AnnualScheduleService;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnualScheduleController extends Controller
{
    public function __construct(
        protected AnnualScheduleService $scheduleService
    ) {}

    /**
     * 年間スケジュール一覧・カレンダー画面の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AnnualSchedule::class);

        // 現在日時を取得
        $now = new DateTimeImmutable;

        // GETパラメータから表示モード、年、月を取得
        $viewMode = $request->input('view', 'calendar');
        $year = (int) $request->input('year', $now->format('Y'));
        $month = (int) $request->input('month', $now->format('n'));

        // GETパラメータからカテゴリとステータスを取得
        $category = $request->input('category');
        $status = $request->input('status');

        // GETパラメータから検索キーワードと担当者IDを取得
        $search = $request->input('search');
        $staffId = $request->filled('staff_id') ? (int) $request->input('staff_id') : null;

        // 一覧表示用のデータを取得
        $listViewData = $this->scheduleService->getIndexViewData($year, $category, $status, $search, $staffId);

        // カレンダー表示用のデータを取得
        $calendarViewData = $this->scheduleService->getCalendarViewData($year, $month, $category, $status, $search, $staffId);

        // ビューに渡すデータをマージ
        $viewData = array_merge($listViewData, $calendarViewData, [
            'viewMode' => $viewMode,
            'selectedYear' => $year,
            'selectedMonth' => $month,
        ]);

        // ビューを表示
        return view('annual_schedules.index', $viewData);
    }

    /**
     * 年間スケジュール新規登録画面の表示
     */
    public function create(): View
    {
        $this->authorize('create', AnnualSchedule::class);

        return view('annual_schedules.create', [
            'categories' => AnnualSchedule::CATEGORIES,
            'statuses' => AnnualSchedule::STATUSES,
            'staffs' => Staff::active()->orderBy('kana')->get(),
        ]);
    }

    /**
     * 年間スケジュール新規登録処理
     */
    public function store(StoreAnnualScheduleRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', AnnualSchedule::class);

        $schedule = $this->scheduleService->createSchedule(
            $request->validated(),
            $request->user()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "年間スケジュール「{$schedule->title}」を登録しました。",
                'schedule' => $schedule->load('assignee'),
            ], 201);
        }

        return redirect()->route('annual-schedules.index', [
            'year' => $schedule->target_year,
        ])->with('success', "年間スケジュール「{$schedule->title}」を登録しました。");
    }

    /**
     * 年間スケジュール詳細画面の表示
     */
    public function show(AnnualSchedule $annualSchedule): View
    {
        $this->authorize('view', $annualSchedule);

        $annualSchedule->load(['creator', 'assignee']);

        return view('annual_schedules.show', [
            'schedule' => $annualSchedule,
        ]);
    }

    /**
     * 年間スケジュール編集画面の表示
     */
    public function edit(AnnualSchedule $annualSchedule): View
    {
        $this->authorize('update', $annualSchedule);

        return view('annual_schedules.edit', [
            'schedule' => $annualSchedule,
            'categories' => AnnualSchedule::CATEGORIES,
            'statuses' => AnnualSchedule::STATUSES,
            'staffs' => Staff::active()->orderBy('kana')->get(),
        ]);
    }

    /**
     * 年間スケジュール更新処理
     */
    public function update(UpdateAnnualScheduleRequest $request, AnnualSchedule $annualSchedule): RedirectResponse
    {
        $this->authorize('update', $annualSchedule);

        $this->scheduleService->updateSchedule($annualSchedule, $request->validated());

        return redirect()->route('annual-schedules.show', $annualSchedule)
            ->with('success', "年間スケジュール「{$annualSchedule->title}」を更新しました。");
    }

    /**
     * 年間スケジュール削除処理
     */
    public function destroy(AnnualSchedule $annualSchedule): RedirectResponse
    {
        $this->authorize('delete', $annualSchedule);

        $title = $annualSchedule->title;
        $year = $annualSchedule->target_year;

        $this->scheduleService->deleteSchedule($annualSchedule);

        return redirect()->route('annual-schedules.index', ['year' => $year])
            ->with('success', "年間スケジュール「{$title}」を削除しました。");
    }
}
