<?php

namespace App\Http\Controllers;

use App\Models\FeatureSetting;
use App\Presenters\TimetablePresenter;
use App\Services\FeatureSettingService;
use App\Services\TimetableService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected TimetableService $timetableService,
        protected FeatureSettingService $featureSettingService,
    ) {}

    /**
     * トップページの表示
     */
    public function __invoke(): View
    {
        $timetables = $this->timetableService->getAllTimetables()
            ->filter(fn ($t) => $t->target_date !== null)
            ->sortBy('target_date')
            ->values();

        $eventDateLabel = TimetablePresenter::formatEventDateRange($timetables);
        $selfRegistrationStatus = $this->featureSettingService->getFeatureStatus(
            FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION
        );

        return view('welcome', [
            'eventDateLabel' => $eventDateLabel,
            'selfRegistrationStatus' => $selfRegistrationStatus,
        ]);
    }
}
