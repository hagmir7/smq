<?php

namespace App\Providers;

use App\Models\ImprovementAction;
use App\Models\ImprovementSheet;
use App\Observers\ImprovementActionObserver;
use App\Observers\ImprovementSheetObserver;
use Illuminate\Support\ServiceProvider;
use App\Models\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;



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
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        ImprovementSheet::observe(ImprovementSheetObserver::class);
        ImprovementAction::observe(ImprovementActionObserver::class);
    }
}
