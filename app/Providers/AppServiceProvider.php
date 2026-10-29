<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
    // public function boot(): void
    // {
    //     //
    // }

    public function boot(): void
    {
        // Force HTTPS pour toutes les URLs générées
        URL::forceScheme('https');

        // Force le domaine racine (le tunnel ngrok ou ton domaine)
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }
    }
}
