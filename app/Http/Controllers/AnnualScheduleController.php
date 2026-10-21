<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnualScheduleRequest;
use App\Http\Requests\UpdateAnnualScheduleRequest;
use App\Models\AnnualSchedule;
use App\Models\Staff;
use App\Services\AnnualScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnualScheduleController extends Controller
{
    public function __construct(
        protected AnnualScheduleService $scheduleService
    ) {}

    /**
     * 年間スケジュール一覧・絞り込み画面の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AnnualSchedule::class);

        $viewData = $this->scheduleService->getIndexViewData(
            year: (int) $request->input('year', 2026),
            category: $request->input('category'),
            status: $request->input('status'),
            search: $request->input('search'),
            staffId: $request->filled('staff_id') ? (int) $request->input('staff_id') : null
        );

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
    public function store(StoreAnnualScheduleRequest $request): RedirectResponse
    {
        $this->authorize('create', AnnualSchedule::class);

        $schedule = $this->scheduleService->createSchedule(
            $request->validated(),
            $request->user()
        );

        return redirect()->route('annual-schedules.index', ['year' => $schedule->target_year])
            ->with('success', "年間スケジュール「{$schedule->title}」を登録しました。");
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
