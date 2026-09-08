<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $settings = cache()->remember('site_settings', 3600, function () {
                return SiteSetting::pluck('value', 'key')->toArray();
            });

            $view->with('settings', $settings)->with('landingImages', [
                'hero' => SiteSetting::imageUrl($settings['landing_hero_image'] ?? null, SiteSetting::DEFAULT_LANDING_HERO_IMAGE),
                'about' => SiteSetting::imageUrl($settings['about_image'] ?? null, SiteSetting::DEFAULT_ABOUT_IMAGE),
            ]);
        });
    }
}
