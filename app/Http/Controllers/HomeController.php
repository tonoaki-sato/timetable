<?php

namespace App\Http\Controllers;

use App\Models\FeatureSetting;
use App\Models\Timetable;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Services\FeatureSettingService;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected TimetableRepositoryInterface $timetableRepository,
        protected FeatureSettingService $featureSettingService,
    ) {}

    /**
     * トップページの表示
     */
    public function __invoke(): View
    {
        $timetables = $this->timetableRepository->getAll()
            ->filter(fn ($t) => $t->target_date !== null)
            ->sortBy('target_date')
            ->values();

        $eventDateLabel = $this->buildEventDateLabel($timetables);
        $selfRegistrationStatus = $this->featureSettingService->getFeatureStatus(
            FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION
        );

        return view('welcome', [
            'eventDateLabel' => $eventDateLabel,
            'selfRegistrationStatus' => $selfRegistrationStatus,
        ]);
    }

    /**
     * 登録済み運行日程から開催期間ラベルを構築
     *
     * @param  Collection<int, Timetable>  $timetables
     */
    protected function buildEventDateLabel(Collection $timetables): string
    {
        if ($timetables->isEmpty()) {
            return '開催日程準備中';
        }

        $first = $timetables->first();
        $last = $timetables->last();

        if (! $first || ! $first->target_date || ! $last || ! $last->target_date) {
            return '開催日程準備中';
        }

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $startStr = $first->target_date->format('Y年n月j日').'('.$weekdays[$first->target_date->dayOfWeek].')';

        if ($first->target_date->isSameDay($last->target_date)) {
            return $startStr.' 開催';
        }

        $endPrefix = $first->target_date->year === $last->target_date->year
            ? $last->target_date->format('n月j日')
            : $last->target_date->format('Y年n月j日');
        $endStr = $endPrefix.'('.$weekdays[$last->target_date->dayOfWeek].')';

        return "{$startStr} 〜 {$endStr} 開催";
    }
}
