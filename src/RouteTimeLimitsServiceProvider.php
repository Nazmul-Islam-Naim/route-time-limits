<?php

namespace NTimes\RouteTimeLimits;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use NTimes\RouteTimeLimits\Http\Middleware\RouteTimeLimit;
use NTimes\RouteTimeLimits\Commands\CleanupTimeRecordsCommand;
use NTimes\RouteTimeLimits\Commands\ResetTimeLimitsCommand;

class RouteTimeLimitsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register the Filesystem service provider to bind 'files'
        $this->app->register(\Illuminate\Filesystem\FilesystemServiceProvider::class);

        // Register the Cache service provider to bind 'cache'
        $this->app->register(\Illuminate\Cache\CacheServiceProvider::class);

        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/config/route_time_limits.php', 'route_time_limits'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Register middleware
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('route.time.limit', RouteTimeLimit::class);
        
        // Publish config
        $this->publishes([
            __DIR__ . '/config/route_time_limits.php' => config_path('route_time_limits.php'),
        ], 'config');
        
        // Publish migrations
        $this->publishes([
            __DIR__ . '/database/migrations/create_route_time_limits_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_route_time_limits_table.php'),
        ], 'migrations');

         // Publish the views to the Laravel app's resources/views/route-time-limits directory
         $this->publishes([
            __DIR__ . '/resources/views' => resource_path('views/route-time-limits'),
        ], 'route-time-limit-views');
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanupTimeRecordsCommand::class,
                ResetTimeLimitsCommand::class,
            ]);
            
            // Schedule command to run daily
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
                $schedule->command('route-time-limits:cleanup')->daily();
            });
        }
    }
}