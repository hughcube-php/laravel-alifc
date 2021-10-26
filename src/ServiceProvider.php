<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/18
 * Time: 10:32 下午.
 */

namespace HughCube\Laravel\AliFC;

use HughCube\Laravel\AliFC\Queue\Connector;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Boot the provider.
     */
    public function boot()
    {
        $source = realpath(dirname(__DIR__).'/config/config.php');

        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path(sprintf("%s.php", AliFC::getFacadeAccessor()))]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure(AliFC::getFacadeAccessor());
        }
    }

    /**
     * Register the provider.
     */
    public function register()
    {
        $this->registerManager();
        $this->registerQueueConnector();
    }

    protected function registerQueueConnector()
    {
        $this->app->resolving('queue', function (QueueManager $queue) {
            $queue->extend('alifc', function () {
                return new Connector($this->app['alifc']);
            });
        });
    }

    protected function registerManager()
    {
        $this->app->singleton(AliFC::getFacadeAccessor(), function ($app) {
            return new Manager();
        });
    }
}
