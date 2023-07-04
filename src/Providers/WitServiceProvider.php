<?php
/**
 * Created by PhpStorm.
 * User: hazelcodes
 * Date: 6/20/23
 * Time: 12:54 PM
 */

namespace Hazelveek\PhpWitAi;

use Hazelveek\PhpWitAi\Facades\WitAi;
use Illuminate\Support\ServiceProvider;

class WitServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(WitClient::class, function ($app) {
            // You can customize the instantiation of WitClient if needed
            return new WitClient();
        });

        $this->app->alias(WitClient::class, 'wit');

        $this->mergeConfigFrom(__DIR__.'/config/witai.php', 'witai');
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/witai.php' => $this->app->configPath('witai.php'),
            ], 'config');
        }

        // Register the facade alias
        if (class_exists(WitAi::class)) {
            $this->app->alias(WitAi::class, 'Wit');
        }
    }
}