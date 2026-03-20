<?php

namespace App\Providers;

use App\Models\ExamAllocation;
use App\Policies\ExamPassPolicy;
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
    }
}
