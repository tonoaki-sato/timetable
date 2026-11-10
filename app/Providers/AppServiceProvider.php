<?php

namespace App\Providers;

use App\Models\FeatureSetting;
use App\Presenters\NavigationPresenter;
use App\Repositories\Contracts\EquipmentLogisticsPatternRepositoryInterface;
use App\Repositories\Contracts\EquipmentLogisticsPlanRepositoryInterface;
use App\Repositories\Contracts\EquipmentRepositoryInterface;
use App\Repositories\Contracts\FeatureSettingRepositoryInterface;
use App\Repositories\Contracts\LaneRepositoryInterface;
use App\Repositories\Contracts\LaneRequestRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\LocationScheduleRepositoryInterface;
use App\Repositories\Contracts\LocationTypeRepositoryInterface;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TaskStaffAssignmentRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EquipmentLogisticsPatternRepository;
use App\Repositories\Eloquent\EquipmentLogisticsPlanRepository;
use App\Repositories\Eloquent\EquipmentRepository;
use App\Repositories\Eloquent\FeatureSettingRepository;
use App\Repositories\Eloquent\LaneRepository;
use App\Repositories\Eloquent\LaneRequestRepository;
use App\Repositories\Eloquent\LocationRepository;
use App\Repositories\Eloquent\LocationScheduleRepository;
use App\Repositories\Eloquent\LocationTypeRepository;
use App\Repositories\Eloquent\ScheduleRepository;
use App\Repositories\Eloquent\StaffRepository;
use App\Repositories\Eloquent\TaskStaffAssignmentRepository;
use App\Repositories\Eloquent\TimetableRepository;
use App\Repositories\Eloquent\TimetableTaskRepository;
use App\Repositories\Eloquent\UserRepository;
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

        $this->app->bind(
            LaneRepositoryInterface::class,
            LaneRepository::class
        );

        $this->app->bind(
            LocationRepositoryInterface::class,
            LocationRepository::class
        );

        $this->app->bind(
            EquipmentRepositoryInterface::class,
            EquipmentRepository::class
        );

        $this->app->bind(
            EquipmentLogisticsPlanRepositoryInterface::class,
            EquipmentLogisticsPlanRepository::class
        );

        $this->app->bind(
            EquipmentLogisticsPatternRepositoryInterface::class,
            EquipmentLogisticsPatternRepository::class
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->bind(
            LaneRequestRepositoryInterface::class,
            LaneRequestRepository::class
        );

        $this->app->bind(
            LocationTypeRepositoryInterface::class,
            LocationTypeRepository::class
        );

        $this->app->bind(
            LocationScheduleRepositoryInterface::class,
            LocationScheduleRepository::class
        );

        $this->app->bind(
            TaskStaffAssignmentRepositoryInterface::class,
            TaskStaffAssignmentRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.layouts.app', function ($view): void {
            /** @var TimetableRepositoryInterface $timetableRepository */
            $timetableRepository = app(TimetableRepositoryInterface::class);
            $navTimetables = $timetableRepository->getForNavigation();

            /** @var FeatureSettingService $featureService */
            $featureService = app(FeatureSettingService::class);
            $selfRegistrationSetting = $featureService->getFeature(FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION);
            $selfRegistrationStatus = $featureService->getFeatureStatus(FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION);

            $view->with([
                'navTimetables' => $navTimetables,
                'selfRegistrationSetting' => $selfRegistrationSetting,
                'selfRegistrationStatus' => $selfRegistrationStatus,
                'nav' => new NavigationPresenter($navTimetables),
            ]);
        });
    }
}
