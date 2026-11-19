<?php

namespace App\Providers;

use App\Models\FeatureSetting;
use App\Models\LaneRequest;
use App\Models\Timetable;
use App\Presenters\NavigationPresenter;
use App\Repositories\Contracts\LaneRequestRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Services\FeatureSettingService;
use App\Support\NavigationCache;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(NavigationCache::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Timetable::saved(static function (Timetable $timetable): void {
            app(NavigationCache::class)->forgetForModel($timetable);
        });
        Timetable::deleted(static function (Timetable $timetable): void {
            app(NavigationCache::class)->forgetForModel($timetable);
        });
        FeatureSetting::saved(static function (FeatureSetting $setting): void {
            app(NavigationCache::class)->forgetForModel($setting);
        });
        FeatureSetting::deleted(static function (FeatureSetting $setting): void {
            app(NavigationCache::class)->forgetForModel($setting);
        });
        LaneRequest::saved(static function (LaneRequest $laneRequest): void {
            app(NavigationCache::class)->forgetForModel($laneRequest);
        });
        LaneRequest::deleted(static function (LaneRequest $laneRequest): void {
            app(NavigationCache::class)->forgetForModel($laneRequest);
        });

        View::composer('components.layouts.app', function ($view): void {
            /** @var NavigationCache $navigationCache */
            $navigationCache = app(NavigationCache::class);

            /** @var TimetableRepositoryInterface $timetableRepository */
            $timetableRepository = app(TimetableRepositoryInterface::class);
            $navTimetables = $timetableRepository->getForNavigation();

            /** @var FeatureSettingService $featureService */
            $featureService = app(FeatureSettingService::class);
            $selfRegistrationStatus = $featureService->getFeatureStatus(FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION);
            $selfRegistrationSetting = $selfRegistrationStatus['feature'];

            $headerPendingRequestsCount = 0;
            $headerPendingRequests = collect();
            if (Auth::check()) {
                $pendingSummary = $navigationCache->getPendingLaneRequestsSummary(function (): array {
                    /** @var LaneRequestRepositoryInterface $laneRequestRepository */
                    $laneRequestRepository = app(LaneRequestRepositoryInterface::class);
                    $count = $laneRequestRepository->countPendingGlobal();
                    $recent = $count > 0
                        ? $laneRequestRepository->getRecentPendingGlobal(8)
                        : new EloquentCollection;

                    return [
                        'count' => $count,
                        'recent' => $recent,
                    ];
                });

                $headerPendingRequestsCount = $pendingSummary['count'];
                $headerPendingRequests = $pendingSummary['recent'];
            }

            $view->with([
                'navTimetables' => $navTimetables,
                'selfRegistrationSetting' => $selfRegistrationSetting,
                'selfRegistrationStatus' => $selfRegistrationStatus,
                'headerPendingRequestsCount' => $headerPendingRequestsCount,
                'headerPendingRequests' => $headerPendingRequests,
                'nav' => new NavigationPresenter($navTimetables),
            ]);
        });
    }
}
