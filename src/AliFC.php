<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/23
 * Time: 10:58.
 */

namespace HughCube\Laravel\AliFC;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

/**
 * @method static Client client(string $name = null)
 * @method static Client makeClient(array $config)
 *
 * @see Client
 * @see Manager
 */
class AliFC extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    public static function getFacadeAccessor(): string
    {
        return 'alifc';
    }
}
