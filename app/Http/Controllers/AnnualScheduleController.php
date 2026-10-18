<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnualScheduleRequest;
use App\Http\Requests\UpdateAnnualScheduleRequest;
use App\Models\AnnualSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnualScheduleController extends Controller
{
    /**
     * 年間スケジュール一覧・絞り込み画面の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AnnualSchedule::class);

        $selectedYear = (int) $request->input('year', 2026);

        // 登録されている対象年度一覧（タブ切り替え用）
        $availableYears = AnnualSchedule::query()
            ->select('target_year')
            ->distinct()
            ->orderBy('target_year', 'desc')
            ->pluck('target_year')
            ->all();

        if (empty($availableYears)) {
            $availableYears = [2026];
        } elseif (! in_array(2026, $availableYears, true)) {
            $availableYears[] = 2026;
            rsort($availableYears);
        }

        $query = AnnualSchedule::with('creator');

        if ($selectedYear) {
            $query->where('target_year', $selectedYear);
        }

        // カテゴリ絞り込み
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        // ステータス絞り込み
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // フリーワード検索
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // 年度内ステータス別集計（サマリー表示用）
        $yearSummary = [
            'total' => AnnualSchedule::where('target_year', $selectedYear)->count(),
            'planned' => AnnualSchedule::where('target_year', $selectedYear)->where('status', 'planned')->count(),
            'in_progress' => AnnualSchedule::where('target_year', $selectedYear)->where('status', 'in_progress')->count(),
            'completed' => AnnualSchedule::where('target_year', $selectedYear)->where('status', 'completed')->count(),
        ];

        $schedules = $query->orderBy('start_date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('annual_schedules.index', [
            'schedules' => $schedules,
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
            'yearSummary' => $yearSummary,
            'categories' => AnnualSchedule::CATEGORIES,
            'statuses' => AnnualSchedule::STATUSES,
        ]);
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
        ]);
    }

    /**
     * 年間スケジュール新規登録処理
     */
    public function store(StoreAnnualScheduleRequest $request): RedirectResponse
    {
        $this->authorize('create', AnnualSchedule::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()?->id;

        $schedule = AnnualSchedule::create($data);

        return redirect()->route('annual-schedules.index', ['year' => $schedule->target_year])
            ->with('success', "年間スケジュール「{$schedule->title}」を登録しました。");
    }

    /**
     * 年間スケジュール詳細画面の表示
     */
    public function show(AnnualSchedule $annualSchedule): View
    {
        $this->authorize('view', $annualSchedule);

        $annualSchedule->load('creator');

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
        ]);
    }

    /**
     * 年間スケジュール更新処理
     */
    public function update(UpdateAnnualScheduleRequest $request, AnnualSchedule $annualSchedule): RedirectResponse
    {
        $this->authorize('update', $annualSchedule);

        $annualSchedule->update($request->validated());

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

        $annualSchedule->delete();

        return redirect()->route('annual-schedules.index', ['year' => $year])
            ->with('success', "年間スケジュール「{$title}」を削除しました。");
    }
}
