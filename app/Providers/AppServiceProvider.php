<?php

namespace App\Providers;

use App\Repositories\Contracts\AnnualScheduleRepositoryInterface;
use App\Repositories\Eloquent\AnnualScheduleRepository;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
