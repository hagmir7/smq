<?php

namespace App\Providers;

use App\Models\ImprovementAction;
use App\Models\ImprovementSheet;
use App\Observers\ImprovementActionObserver;
use App\Observers\ImprovementSheetObserver;
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
        ImprovementSheet::observe(ImprovementSheetObserver::class);
        ImprovementAction::observe(ImprovementActionObserver::class);
    }
}
