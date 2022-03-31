<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/31
 * Time: 21:46.
 */

namespace HughCube\Laravel\AliFC\Tests\Actions;

use HughCube\Laravel\AliFC\Actions\InitializeAction;
use HughCube\Laravel\AliFC\Tests\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class InitializeActionTest extends TestCase
{
    public function testAction()
    {
        $action = new InitializeAction();
        $response = $action();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }
}
