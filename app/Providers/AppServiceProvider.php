<?php

namespace App\Providers;

use App\Models\ImprovementAction;
use App\Models\ImprovementSheet;
use App\Observers\ImprovementActionObserver;
use App\Observers\ImprovementSheetObserver;
use Illuminate\Support\ServiceProvider;
use App\Models\PersonalAccessToken;
use Illuminate\Support\Facades\DB;
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
        // DB::statement('SET DATEFORMAT Y-m-d\TH:i:s.v');
        DB::statement('SET DATEFORMAT ymd');
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        ImprovementSheet::observe(ImprovementSheetObserver::class);
        ImprovementAction::observe(ImprovementActionObserver::class);
    }
}
