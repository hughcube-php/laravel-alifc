<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午
 */

namespace HughCube\Laravel\AliFC\Actions;

use Symfony\Component\HttpFoundation\Response;

class PreStopAction extends Action
{
    /**
     * @return Response
     */
    public function action(): Response
    {
        return $this->asJson();
    }
}
