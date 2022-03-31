<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午.
 */

namespace HughCube\Laravel\AliFC\Actions;

use Illuminate\Http\JsonResponse;

class PreStopAction
{
    /**
     * @return JsonResponse
     */
    public function action(): JsonResponse
    {
        return new JsonResponse(['code' => 200, 'message' => 'ok']);
    }

    public function __invoke(): JsonResponse
    {
        return $this->action();
    }
}
