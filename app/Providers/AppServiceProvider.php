<?php

namespace App\Providers;

use App\Models\FeatureSetting;
use App\Models\Timetable;
use App\Repositories\Contracts\FeatureSettingRepositoryInterface;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use App\Repositories\Eloquent\FeatureSettingRepository;
use App\Repositories\Eloquent\ScheduleRepository;
use App\Repositories\Eloquent\StaffRepository;
use App\Repositories\Eloquent\TimetableRepository;
use App\Repositories\Eloquent\TimetableTaskRepository;
use App\Services\FeatureSettingService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ScheduleRepositoryInterface::class,
            ScheduleRepository::class
        );

        $this->app->bind(
            StaffRepositoryInterface::class,
            StaffRepository::class
        );

        $this->app->bind(
            TimetableRepositoryInterface::class,
            TimetableRepository::class
        );

        $this->app->bind(
            TimetableTaskRepositoryInterface::class,
            TimetableTaskRepository::class
        );

        $this->app->bind(
            FeatureSettingRepositoryInterface::class,
            FeatureSettingRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.layouts.app', function ($view): void {
            $navTimetables = Timetable::where('status', '!=', 'completed')
                ->orderBy('target_date')
                ->get();

            /** @var FeatureSettingService $featureService */
            $featureService = app(FeatureSettingService::class);
            $selfRegistrationSetting = $featureService->getFeature(FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION);
            $selfRegistrationStatus = $featureService->getFeatureStatus(FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION);

            $view->with([
                'navTimetables' => $navTimetables,
                'selfRegistrationSetting' => $selfRegistrationSetting,
                'selfRegistrationStatus' => $selfRegistrationStatus,
            ]);
        });
    }
}
