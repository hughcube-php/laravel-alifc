<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/31
 * Time: 21:46.
 */

namespace HughCube\Laravel\AliFC\Tests\Actions;

use HughCube\Laravel\AliFC\Actions\PreFreezeAction;
use HughCube\Laravel\AliFC\Tests\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class PreFreezeActionTest extends TestCase
{
    public function testAction()
    {
        $action = new PreFreezeAction();
        $response = $action();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }
}
