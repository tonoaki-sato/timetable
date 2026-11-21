<?php

namespace App\Providers;

use App\Repositories\Contracts\EquipmentLogisticsPatternRepositoryInterface;
use App\Repositories\Contracts\EquipmentLogisticsPlanRepositoryInterface;
use App\Repositories\Contracts\EquipmentRepositoryInterface;
use App\Repositories\Contracts\FeatureSettingRepositoryInterface;
use App\Repositories\Contracts\LaneRepositoryInterface;
use App\Repositories\Contracts\LaneRequestRepositoryInterface;
use App\Repositories\Contracts\LayoutAreaRepositoryInterface;
use App\Repositories\Contracts\LayoutMapRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\LocationScheduleRepositoryInterface;
use App\Repositories\Contracts\LocationTypeRepositoryInterface;
use App\Repositories\Contracts\OrganizationUnitRepositoryInterface;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\SiteMapRepositoryInterface;
use App\Repositories\Contracts\StaffCheckInRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TaskStaffAssignmentRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Repositories\Eloquent\EquipmentLogisticsPatternRepository;
use App\Repositories\Eloquent\EquipmentLogisticsPlanRepository;
use App\Repositories\Eloquent\EquipmentRepository;
use App\Repositories\Eloquent\FeatureSettingRepository;
use App\Repositories\Eloquent\LaneRepository;
use App\Repositories\Eloquent\LaneRequestRepository;
use App\Repositories\Eloquent\LayoutAreaRepository;
use App\Repositories\Eloquent\LayoutMapRepository;
use App\Repositories\Eloquent\LocationRepository;
use App\Repositories\Eloquent\LocationScheduleRepository;
use App\Repositories\Eloquent\LocationTypeRepository;
use App\Repositories\Eloquent\OrganizationUnitRepository;
use App\Repositories\Eloquent\ScheduleRepository;
use App\Repositories\Eloquent\SiteMapRepository;
use App\Repositories\Eloquent\StaffCheckInRepository;
use App\Repositories\Eloquent\StaffRepository;
use App\Repositories\Eloquent\TaskStaffAssignmentRepository;
use App\Repositories\Eloquent\TimetableRepository;
use App\Repositories\Eloquent\TimetableTaskRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\VendorRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * リポジトリ Contract と Eloquent 実装の対応表
     *
     * @var array<class-string, class-string>
     */
    private const array BINDINGS = [
        ScheduleRepositoryInterface::class => ScheduleRepository::class,
        SiteMapRepositoryInterface::class => SiteMapRepository::class,
        StaffRepositoryInterface::class => StaffRepository::class,
        StaffCheckInRepositoryInterface::class => StaffCheckInRepository::class,
        TimetableRepositoryInterface::class => TimetableRepository::class,
        TimetableTaskRepositoryInterface::class => TimetableTaskRepository::class,
        FeatureSettingRepositoryInterface::class => FeatureSettingRepository::class,
        LaneRepositoryInterface::class => LaneRepository::class,
        LocationRepositoryInterface::class => LocationRepository::class,
        EquipmentRepositoryInterface::class => EquipmentRepository::class,
        EquipmentLogisticsPlanRepositoryInterface::class => EquipmentLogisticsPlanRepository::class,
        EquipmentLogisticsPatternRepositoryInterface::class => EquipmentLogisticsPatternRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
        LaneRequestRepositoryInterface::class => LaneRequestRepository::class,
        LocationTypeRepositoryInterface::class => LocationTypeRepository::class,
        LocationScheduleRepositoryInterface::class => LocationScheduleRepository::class,
        TaskStaffAssignmentRepositoryInterface::class => TaskStaffAssignmentRepository::class,
        OrganizationUnitRepositoryInterface::class => OrganizationUnitRepository::class,
        LayoutAreaRepositoryInterface::class => LayoutAreaRepository::class,
        LayoutMapRepositoryInterface::class => LayoutMapRepository::class,
        VendorRepositoryInterface::class => VendorRepository::class,
    ];

    /**
     * リポジトリバインディングの登録
     */
    public function register(): void
    {
        foreach (self::BINDINGS as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }
    }
}
