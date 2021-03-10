<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/23
 * Time: 11:04
 */

namespace HughCube\Laravel\AliFC;

use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Boot the provider.
     */
    public function boot()
    {
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $source = dirname(__DIR__) . '/config/config.php';
            $this->publishes([$source => config_path('alifc.php')]);
        }

        if ($this->app instanceof LumenApplication) {
            $this->app->configure('alifc');
        }
    }

    /**
     * Register the provider.
     */
    public function register()
    {
        $this->app->singleton(
            "alifc",
            function ($app) {
                /** @var LaravelApplication|LumenApplication $app */
                $config = $app->make('config')->get('alifc', []);

                return new Manager($config);
            }
        );
    }
}
