<?php

namespace App\Providers;

use Statamic\Statamic;
use Statamic\StaticSite\SSG;
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
        // Statamic::vite('app', [
        //     'resources/js/cp.js',
        //     'resources/css/cp.css',
        // ]);

        SSG::after(function () {
            $from = public_path('img');
            $to = config('statamic.ssg.destination') . '/img';

            app('files')->copyDirectory($from, $to);
        });
    }
}
