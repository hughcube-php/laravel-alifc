<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/18
 * Time: 10:32 下午.
 */

namespace HughCube\Laravel\AliFC;

use HughCube\Laravel\AliFC\Actions\InitializeAction;
use HughCube\Laravel\AliFC\Actions\InvokeAction;
use HughCube\Laravel\AliFC\Actions\PreFreezeAction;
use HughCube\Laravel\AliFC\Actions\PreStopAction;
use HughCube\Laravel\AliFC\Commands\JobPayloadCommand;
use HughCube\Laravel\AliFC\Queue\Connector;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

/**
 * @property LumenApplication|Application $app
 */
class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Register the provider.
     */
    public function register()
    {
        $this->app->singleton(AliFC::getFacadeAccessor(), function ($app) {
            return new Manager();
        });

        $this->app->resolving('queue', function (QueueManager $queue) {
            $queue->extend(AliFC::getFacadeAccessor(), function () {
                return new Connector($this->app[AliFC::getFacadeAccessor()]);
            });
        });
    }

    /**
     * Boot the provider.
     */
    public function boot()
    {
        $this->bootCommands();
        $this->bootHandlers();
    }

    protected function bootCommands()
    {
        $this->commands([
            JobPayloadCommand::class,
        ]);
    }

    /**
     * Define the Sanctum routes.
     *
     * @return void
     */
    protected function bootHandlers()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        /** @var Repository $config */
        $config = $this->app->make('config');

        if (false !== ($handler = $config->get('alifc.handlers.initialize', InitializeAction::class))) {
            Route::any('/initialize', $handler)->name('alifc.handler.initialize');
        }

        if (false !== ($handler = $config->get('alifc.handlers.invoke', InvokeAction::class))) {
            Route::any('/invoke', $handler)->name('alifc.handler.invoke');
        }

        if (false !== ($handler = $config->get('alifc.handlers.pre_freeze', PreFreezeAction::class))) {
            Route::any('/pre-freeze', $handler)->name('alifc.handler.preFreeze');
        }

        if (false !== ($handler = $config->get('alifc.handlers.pre_stop', PreStopAction::class))) {
            Route::any('/pre-stop', $handler)->name('alifc.handler.preStop');
        }
    }
}
