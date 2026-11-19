<?php

namespace App\Actions\Timetable;

use App\Models\Timetable;
use App\Services\TimetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResolveActiveTimetableRedirectAction
{
    public function __construct(
        protected TimetableService $timetableService
    ) {}

    /**
     * リクエストされたIDまたはアクティブ／直近の運行計画を解決
     */
    public function resolveRequestedOrActive(?int $timetableId): ?Timetable
    {
        if ($timetableId !== null && $timetableId > 0) {
            return $this->timetableService->findOrFail($timetableId);
        }

        return $this->timetableService->findFirstActiveOrEarliest();
    }

    /**
     * 対象ルート名へタイムテーブル付きでリダイレクト（存在しない場合は一覧へ遷移）
     */
    public function execute(Request $request, string $targetRouteName): RedirectResponse
    {
        $timetableId = $request->filled('timetable_id') ? $request->integer('timetable_id') : null;
        $timetable = $this->resolveRequestedOrActive($timetableId);

        if ($timetable !== null) {
            return redirect()->route($targetRouteName, $timetable);
        }

        return redirect()->route('timetables.index');
    }
}
