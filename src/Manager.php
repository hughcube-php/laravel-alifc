<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/20
 * Time: 4:19 下午.
 */

namespace HughCube\Laravel\AliFC;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Support\Manager as IlluminateManager;
use InvalidArgumentException;

/**
 * @mixin Client
 */
class Manager extends IlluminateManager
{
    /**
     * @param  callable|ContainerContract|null  $container
     */
    public function __construct($container = null)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerContract
     */
    public function getContainer(): ContainerContract
    {
        if (is_callable($this->container)) {
            return call_user_func($this->container);
        }

        if (null === $this->container) {
            return IlluminateContainer::getInstance();
        }

        return $this->container;
    }

    /**
     * @param  string  $name
     * @param  null  $default
     * @return mixed
     * @throws BindingResolutionException
     */
    public function getConfig(string $name, $default = null): mixed
    {
        /** @var Repository $config */
        $config = $this->getContainer()->make('config');

        $key = sprintf("%s.%s", AliFC::getFacadeAccessor(), $name);
        return $config->get($key, $default);
    }

    /**
     * @inheritDoc
     * @throws BindingResolutionException
     */
    public function getDefaultDriver(): string
    {
        return $this->getConfig("default", "default");
    }

    /**
     * Get the configuration for a store.
     *
     * @param  string|null  $name
     *
     * @return array
     * @throws InvalidArgumentException|BindingResolutionException
     */
    protected function configuration(string $name = null): array
    {
        $name = $name ?: $this->getDefaultDriver();
        $config = $this->getConfig("clients.$name", []);
        $config = array_merge($config, $this->getConfig("defaults", []));

        if (empty($config)) {
            throw new InvalidArgumentException("Client [{$name}] not configured.");
        }

        return $config;
    }

    /**
     * @param  string  $driver
     * @return Client
     * @throws BindingResolutionException
     */
    protected function createDriver($driver): Client
    {
        return new Client($this->configuration($driver));
    }

    public function client($name = null): Client
    {
        return $this->driver($name);
    }

    /**
     * @inheritDoc
     */
    public function extend($driver, Closure $callback): static
    {
        return parent::extend($driver, $callback->bindTo($this, $this));
    }
}
