<?php

namespace App\Providers;

use App\Repositories\Contracts\AnnualScheduleRepositoryInterface;
use App\Repositories\Contracts\MeetingRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use App\Repositories\Eloquent\AnnualScheduleRepository;
use App\Repositories\Eloquent\MeetingRepository;
use App\Repositories\Eloquent\StaffRepository;
use App\Repositories\Eloquent\TimetableRepository;
use App\Repositories\Eloquent\TimetableTaskRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            AnnualScheduleRepositoryInterface::class,
            AnnualScheduleRepository::class
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
            MeetingRepositoryInterface::class,
            MeetingRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
