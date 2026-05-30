<?php

namespace App\Providers;

use App\Models\Exam;
use App\Models\ExamAllocation;
use App\Models\Hall;
use App\Policies\ExamPolicy;
use App\Policies\ExamPassPolicy;
use App\Policies\HallPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ExamAllocation::class, ExamPassPolicy::class);
        Gate::policy(Exam::class, ExamPolicy::class);
        Gate::policy(Hall::class, HallPolicy::class);
    }
}
