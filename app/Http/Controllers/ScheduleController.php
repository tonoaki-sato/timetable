<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Models\Schedule;
use App\Models\Staff;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(
        protected ScheduleService $scheduleService
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
        $tab = $request->input('tab');

        $viewData = $this->scheduleService->getIndexViewData(
            category: $category,
            status: $status,
            search: $search,
            staffId: $staffId,
            tab: $tab,
        );

        return view('schedules.index', $viewData);
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
            'staffs' => Staff::active()->orderBy('kana')->get(),
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
            'staffs' => Staff::active()->orderBy('kana')->get(),
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
                ->with('error', "予定「{$title}」は運行スケジュール（タイムテーブル）と自動連動しているため、スケジュール管理画面からは削除できません。タイムテーブル管理画面から削除してください。");
        }

        $this->scheduleService->deleteSchedule($schedule);

        return redirect()->route('schedules.index')
            ->with('success', "予定「{$title}」を削除しました。");
    }
}
