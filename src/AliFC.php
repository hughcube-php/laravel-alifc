<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/23
 * Time: 10:58.
 */

namespace HughCube\Laravel\AliFC;

use Illuminate\Support\Facades\Facade as IlluminateFacade;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * @method static Client client(string $name = null)
 * @method static Client makeClient(array $config)
 * @method static Response invoke(string $service, string $function, ?string $qualifier = null, ?string $payload = null, array $options = [])
 * @method static Response request(string $method, string $path, array $options = [])
 *
 * @method static string storagePath(string $path = '')
 *
 * @see \HughCube\Laravel\AliFC\Client
 * @see \HughCube\Laravel\AliFC\Manager
 */
class AliFC extends IlluminateFacade
{
    protected static $RId;

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    public static function getFacadeAccessor(): string
    {
        return 'alifc';
    }

    /**
     * Gets the current life cycle ID.
     * @return mixed
     */
    public static function getRId()
    {
        if (empty(static::$RId)) {
            static::$RId = Str::random();
        }
        return static::$RId;
    }
}
