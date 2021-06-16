<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/23
 * Time: 11:21
 */

namespace HughCube\Laravel\AliFC;

use Illuminate\Support\Arr;

class Manager
{
    /**
     * The alifc server configurations.
     *
     * @var array
     */
    protected $config;

    /**
     * The clients.
     *
     * @var Client[]
     */
    protected $clients = [];

    /**
     * Manager constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get a client by name.
     *
     * @param string|null $name
     *
     * @return Client
     */
    public function client($name = null)
    {
        $name = null == $name ? $this->getDefaultClient() : $name;

        if (!isset($this->clients[$name])) {
            $this->clients[$name] = $this->resolve($name);
        }

        return $this->clients[$name];
    }

    /**
     * Resolve the given client by name.
     *
     * @param string|null $name
     *
     * @return Client
     *
     */
    protected function resolve($name = null)
    {
        return $this->makeClient($this->configuration($name));
    }

    /**
     * Make the alifc client instance.
     *
     * @param string|array $config
     * @return Client
     */
    public function makeClient($config)
    {
        return new Client($config);
    }

    /**
     * Make the alifc client instance from alibabaCloud
     *
     * @param null|string $alibabaCloud
     * @return Client
     */
    public function makeClientFromAlibabaCloud($alibabaCloud = null)
    {
        return static::makeClient(['alibabaCloud' => $alibabaCloud]);
    }

    /**
     * Get the default client name.
     *
     * @return string
     */
    public function getDefaultClient()
    {
        return Arr::get($this->config, 'default', 'default');
    }

    /**
     * Get the configuration for a client.
     *
     * @param string $name
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function configuration($name)
    {
        $name = $name ?: $this->getDefaultClient();
        $clients = Arr::get($this->config, 'clients');

        if (is_null($config = Arr::get($clients, $name))) {
            throw new \InvalidArgumentException("Alifc client [{$name}] not configured.");
        }

        return $config;
    }
}
