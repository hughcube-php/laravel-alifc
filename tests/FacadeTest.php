<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/15
 * Time: 7:14 下午.
 */

namespace HughCube\Laravel\AliFC\Tests;

use AliyunFC\Client as FCClient;
use HughCube\Laravel\AliFC\AliFC;
use HughCube\Laravel\AliFC\Client;

class FacadeTest extends TestCase
{
    public function testInstanceOf()
    {
        $this->assertInstanceOf(Client::class, AliFC::client());
        $this->assertInstanceOf(FCClient::class, AliFC::client());
    }
}
