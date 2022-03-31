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
use HughCube\Laravel\Knight\Http\Middleware\HttpsGuard;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Http\Request;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Support\Str;
use Laravel\Lumen\Application as LumenApplication;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Boot the provider.
     */
    public function boot()
    {
        $this->bootPublishes();
        $this->bootCommands();
        $this->bootHandlers();
        $this->bootHttpsGuard();
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

    protected function bootPublishes()
    {
        $source = realpath(dirname(__DIR__).'/config/config.php');
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path(sprintf('%s.php', AliFC::getFacadeAccessor()))]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure(AliFC::getFacadeAccessor());
        }
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
        $handler = config('alifc.handlers.initialize', InitializeAction::class);
        if (! app()->routesAreCached() && false !== $handler) {
            Route::any('/initialize', $handler)->name('alifc_handler_initialize');
        }

        $handler = config('alifc.handlers.invoke', InvokeAction::class);
        if (! app()->routesAreCached() && false !== $handler) {
            Route::any('/invoke', $handler)->name('alifc_handler_invoke');
        }

        $handler = config('alifc.handlers.pre_freeze', PreFreezeAction::class);
        if (! app()->routesAreCached() && false !== $handler) {
            Route::any('/pre-freeze', $handler)->name('alifc_handler_pre_freeze');
        }

        $handler = config('alifc.handlers.pre_freeze', PreStopAction::class);
        if (! app()->routesAreCached() && false !== $handler) {
            Route::any('/pre-stop', $handler)->name('alifc_handler_pre_stop');
        }
    }

    protected function bootHttpsGuard()
    {
        if (! class_exists(HttpsGuard::class)) {
            return;
        }

        HttpsGuard::customExcept(sprintf('%s-%s', md5(__METHOD__), crc32(__METHOD__)), function (Request $request) {
            $fcHeaderCount = 0;
            foreach ($request->headers->all() as $name => $values) {
                if (Str::startsWith($name, 'x-fc-')) {
                    $fcHeaderCount++;
                }
            }

            if ($fcHeaderCount < 5) {
                return false;
            }

            foreach (['initialize', 'invoke', 'pre-freeze', 'pre-stop'] as $uri) {
                if ($request->fullUrlIs($uri) || $request->is($uri)) {
                    return true;
                }
            }

            return false;
        });
    }
}
