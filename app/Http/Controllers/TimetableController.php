<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimetableRequest;
use App\Http\Requests\UpdateTimetableRequest;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Services\ConflictDetectionService;
use App\Services\TimetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        return redirect()->route('timetables.show', $timetable)
            ->with('success', "タイムテーブル「{$timetable->name}」を登録しました。");
    }

    /**
     * タイムテーブル詳細画面の表示（タスク一覧含む）
     */
    public function show(Timetable $timetable): View
    {
        $this->authorize('view', $timetable);

        $detailedTimetable = $this->timetableService->getTimetableDetails($timetable->id) ?? $timetable;
        $otherTimetables = Timetable::where('id', '!=', $timetable->id)->orderBy('target_date')->get();

        return view('timetables.show', [
            'timetable' => $detailedTimetable,
            'otherTimetables' => $otherTimetables,
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

    /**
     * 他の運行日からスタッフアサインを一括コピー
     */
    public function copyAssignmentsFrom(Request $request, Timetable $timetable): RedirectResponse
    {
        $this->authorize('update', $timetable);

        $validated = $request->validate([
            'source_timetable_id' => ['required', 'integer', 'exists:timetables,id', 'different:timetable'],
        ], [
            'source_timetable_id.different' => 'コピー元には異なる運行タイムテーブルを選択してください。',
        ]);

        $sourceTimetable = Timetable::with(['tasks.assignments.staff'])->findOrFail($validated['source_timetable_id']);
        $sourceTasks = $sourceTimetable->tasks;

        if ($sourceTasks->isEmpty()) {
            return back()->with('error', "コピー元「{$sourceTimetable->name}」に作業タスクが登録されていません。");
        }

        $targetTasks = $timetable->tasks()->with(['assignments'])->get();
        if ($targetTasks->isEmpty()) {
            return back()->with('error', "コピー先「{$timetable->name}」に作業タスクがありません。先にタスクまたはレーンを作成してください。");
        }

        $copiedCount = 0;
        $skippedConflictCount = 0;
        $matchedTaskCount = 0;

        DB::transaction(function () use ($sourceTasks, $targetTasks, &$copiedCount, &$skippedConflictCount, &$matchedTaskCount) {
            /** @var ConflictDetectionService $conflictService */
            $conflictService = app(ConflictDetectionService::class);

            foreach ($sourceTasks as $srcTask) {
                if ($srcTask->assignments->isEmpty()) {
                    continue;
                }

                // 対応するコピー先タスクを探索（同一レーン名 かつ 同一開始・終了時刻）
                $targetTask = $targetTasks->first(function (TimetableTask $t) use ($srcTask) {
                    return $t->lane === $srcTask->lane
                        && substr($t->start_time, 0, 5) === substr($srcTask->start_time, 0, 5)
                        && substr($t->end_time, 0, 5) === substr($srcTask->end_time, 0, 5);
                });

                // 見つからない場合はタイトル一致で探索フォールバック
                if (! $targetTask) {
                    $targetTask = $targetTasks->first(function (TimetableTask $t) use ($srcTask) {
                        return $t->title === $srcTask->title;
                    });
                }

                if (! $targetTask) {
                    continue;
                }

                $matchedTaskCount++;

                foreach ($srcTask->assignments as $assignment) {
                    $staffId = $assignment->staff_id;

                    // 既にターゲットタスクにアサインされているか
                    if ($targetTask->assignments->contains('staff_id', $staffId)) {
                        continue;
                    }

                    // コピー先で同一時間帯に重複がないかチェック
                    $hasConflict = $conflictService->checkConflict($targetTask, $staffId);
                    if ($hasConflict) {
                        $skippedConflictCount++;

                        continue;
                    }

                    TaskStaffAssignment::create([
                        'timetable_task_id' => $targetTask->id,
                        'staff_id' => $staffId,
                        'role_description' => $assignment->role_description,
                        'attendance_status' => 'assigned',
                    ]);

                    $copiedCount++;
                }
            }
        });

        $msg = "「{$sourceTimetable->name}」から {$matchedTaskCount}件のタスクに対応する {$copiedCount}名のアサインをコピーしました。";
        if ($skippedConflictCount > 0) {
            $msg .= "（同時刻の重複により {$skippedConflictCount}件を安全にスキップしました）";
        }

        return back()->with('success', $msg);
    }
}
