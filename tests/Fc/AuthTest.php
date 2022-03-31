<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/31
 * Time: 22:52.
 */

namespace HughCube\Laravel\AliFC\Tests\Fc;

use HughCube\Laravel\AliFC\Fc\Auth;
use HughCube\Laravel\AliFC\Tests\TestCase;
use Illuminate\Support\Str;
use ReflectionException;

class AuthTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testGetConfig()
    {
        $config = ['uuid' => ($uuid = Str::uuid()->toString())];
        $auth = new class($config) extends Auth
        {
        };
        $this->assertSame($uuid, $this->callMethod($auth, 'getConfig', ['uuid']));
    }
}
