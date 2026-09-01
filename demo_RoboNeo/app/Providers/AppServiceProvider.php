<?php

namespace App\Providers;

use App\Services\RoboNeo\Contracts\RoboNeoGateway;
use App\Services\RoboNeo\DryRunRoboNeoGateway;
use App\Services\RoboNeo\LiveRoboNeoGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RoboNeoGateway::class, function ($app): RoboNeoGateway {
            return $app->make(config('roboneo.live_enabled')
                ? LiveRoboNeoGateway::class
                : DryRunRoboNeoGateway::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
