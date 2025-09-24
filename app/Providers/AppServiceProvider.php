<?php

namespace App\Providers;

use App\Protectors\AuthVerified;
use Illuminate\Support\ServiceProvider;
use Statamic\Auth\Protect\ProtectorManager;
use Statamic\Facades\Preference;
use Statamic\Statamic;

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
        $this->configureStatamicVite();
        $this->configureStatamicPreference();
        $this->configureStatamicProtectors();
    }

    /**
     * Register custom Protector driver for Statamic.
     *
     * @see https://statamic.dev/protecting-content#registering-the-driver
     */
    private function configureStatamicProtectors(): void
    {
        app(ProtectorManager::class)->extend('auth_verified', function ($app) {
            return new AuthVerified;
        });
    }

    private function configureStatamicVite(): void
    {
        Statamic::vite('app', [
            'resources/js/cp.js',
            // 'resources/css/cp.css',
        ]);
    }

    private function configureStatamicPreference(): void
    {
        Preference::extend(
            fn () => [
                'toolbar' => [
                    'display' => __('Toolbar'),
                    'fields' => [
                        'environments' => [
                            'type' => 'section',
                            'display' => __('Environments'),
                            'instructions' => __(
                                'When to render the toolbar. Additionally, it renders only if a user has edit entries permission.'
                            ),
                        ],
                        'show_toolbar_local' => [
                            'display' => __('Local'),
                            'type' => 'toggle',
                            'width' => 33,
                        ],
                        'show_toolbar_staging' => [
                            'display' => __('Staging'),
                            'type' => 'toggle',
                            'width' => 33,
                        ],
                        'show_toolbar_production' => [
                            'display' => __('Production'),
                            'type' => 'toggle',
                            'width' => 33,
                        ],
                    ],
                ],
            ]
        );
    }
}
