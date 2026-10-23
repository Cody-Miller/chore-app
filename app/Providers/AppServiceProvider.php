<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Support\Timezone;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(Timezone::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // A copy of the date in the viewer's timezone, for showing it. Never store the result.
        Carbon::macro('inViewerTz', function () {
            return $this->copy()->setTimezone(Timezone::viewer());
        });
    }
}
