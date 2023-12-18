<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/20
 * Time: 4:19 下午.
 */

namespace HughCube\Laravel\AliFC;

use HughCube\Laravel\ServiceSupport\Manager as ServiceSupportManager;

/**
 * @mixin Client
 */
class Manager extends ServiceSupportManager
{
    protected function makeDriver(array $config): Client
    {
        return new Client(new Config\Config($config));
    }

    protected function makeClient(array $config): Client
    {
        return $this->makeDriver($config);
    }

    protected function getPackageFacadeAccessor(): string
    {
        return AliFC::getFacadeAccessor();
    }

    public function getDriversConfigKey(): string
    {
        return 'clients';
    }
}
