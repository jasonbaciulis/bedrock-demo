<?php

namespace App\Providers;

use Statamic\Statamic;
use Illuminate\Support\Arr;
use Statamic\StaticSite\SSG;
use Illuminate\Support\Facades\Artisan;
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

        if (
            isset($_SERVER['argv']) &&
            count($_SERVER['argv']) >= 2 &&
            (($_SERVER['argv'][0] === 'please' && $_SERVER['argv'][1] === 'ssg:generate') ||
                ($_SERVER['argv'][0] === 'artisan' &&
                    $_SERVER['argv'][1] === 'statamic:ssg:generate'))
        ) {
            // get the directory
            $directory = Arr::get(config('statamic.ssg'), 'glide.directory');

            // get the original config
            $originalCacheConfig = config('statamic.assets.image_manipulation');

            // update the config to enable image manipulation caching
            config([
                'statamic.assets.image_manipulation.cache' => true,
                'statamic.assets.image_manipulation.cache_path' =>
                    config('statamic.ssg.destination') . DIRECTORY_SEPARATOR . $directory,
            ]);

            SSG::after(function () use ($originalCacheConfig) {
                // reset the config to what it was before the script ran
                config([
                    'statamic.assets.image_manipulation.cache' => $originalCacheConfig['cache'],
                    'statamic.assets.image_manipulation.cache_path' =>
                        $originalCacheConfig['cache_path'],
                ]);

                // clear the image cache
                Artisan::call('statamic:glide:clear');
            });
        }
    }
}
