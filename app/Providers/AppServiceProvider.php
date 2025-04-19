<?php

namespace App\Providers;

use Apachish\Dabelna\App\Models\Game;
use Apachish\Dabelna\App\Observers\GameObserver;
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
        //
    }
}
