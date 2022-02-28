<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午
 */

namespace HughCube\Laravel\AliFC\Actions;

use HughCube\Laravel\Knight\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class InitializeAction extends Controller
{
    /**
     * @return Response
     */
    public function action(): Response
    {
        return $this->asJson();
    }
}
