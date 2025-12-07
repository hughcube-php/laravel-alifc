<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午.
 */

namespace HughCube\Laravel\AliFC\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PreStopAction extends Action
{
    protected function action(): JsonResponse
    {
        $start = microtime(true);

        $response = parent::action();

        $end = microtime(true);
        $duration = ($end - $start) * 1000;

        Log::channel()->info(sprintf(
            'PreStop action completed, duration: %.5fms',
            $duration
        ));

        return $response;
    }
}
