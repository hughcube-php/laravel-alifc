<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/23
 * Time: 10:58.
 */

namespace HughCube\Laravel\AliFC;

use Illuminate\Support\Facades\Facade as IlluminateFacade;
use Psr\Http\Message\ResponseInterface;

/**
 * @method static Client client(string $name = null)
 * @method static ResponseInterface invoke(string $service, string $function, ?string $qualifier = null, ?string $payload = null, array $options = [])
 * @method static ResponseInterface request(string $method, string $path, array $options = [])
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
