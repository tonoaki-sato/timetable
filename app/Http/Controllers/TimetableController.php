<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimetableRequest;
use App\Http\Requests\UpdateTimetableRequest;
use App\Models\Timetable;
use App\Services\TimetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableController extends Controller
{
    public function __construct(
        protected TimetableService $timetableService
    ) {}

    /**
     * タイムテーブル一覧（運行日一覧）の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Timetable::class);

        $viewData = $this->timetableService->getIndexViewData(
            status: $request->input('status')
        );

        return view('timetables.index', $viewData);
    }

    /**
     * タイムテーブル新規登録画面の表示
     */
    public function create(): View
    {
        $this->authorize('create', Timetable::class);

        return view('timetables.create', [
            'statuses' => Timetable::STATUSES,
        ]);
    }

    /**
     * タイムテーブル新規登録処理
     */
    public function store(StoreTimetableRequest $request): RedirectResponse
    {
        $this->authorize('create', Timetable::class);

        $timetable = $this->timetableService->createTimetable(
            $request->validated(),
            $request->user()
        );

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', "タイムテーブル「{$timetable->name}」を登録しました。続けて各班の班長および固定スタッフを編成しましょう（Step 3）。");
    }

    /**
     * タイムテーブル詳細画面の表示（タスク一覧含む）
     */
    public function show(Timetable $timetable): View
    {
        $this->authorize('view', $timetable);

        $detailedTimetable = $this->timetableService->getTimetableDetails($timetable->id) ?? $timetable;

        return view('timetables.show', [
            'timetable' => $detailedTimetable,
        ]);
    }

    /**
     * タイムテーブル編集画面の表示
     */
    public function edit(Timetable $timetable): View
    {
        $this->authorize('update', $timetable);

        return view('timetables.edit', [
            'timetable' => $timetable,
            'statuses' => Timetable::STATUSES,
        ]);
    }

    /**
     * タイムテーブル更新処理
     */
    public function update(UpdateTimetableRequest $request, Timetable $timetable): RedirectResponse
    {
        $this->authorize('update', $timetable);

        $this->timetableService->updateTimetable($timetable, $request->validated());

        return redirect()->route('timetables.show', $timetable)
            ->with('success', "タイムテーブル「{$timetable->name}」を更新しました。");
    }

    /**
     * タイムテーブル削除処理
     */
    public function destroy(Timetable $timetable): RedirectResponse
    {
        $this->authorize('delete', $timetable);

        $name = $timetable->name;
        $this->timetableService->deleteTimetable($timetable);

        return redirect()->route('timetables.index')
            ->with('success', "タイムテーブル「{$name}」を削除しました。");
    }
}
