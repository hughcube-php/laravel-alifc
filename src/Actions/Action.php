<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午
 */

namespace HughCube\Laravel\AliFC\Actions;

use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Lumen\Application as LumenApplication;
use Symfony\Component\HttpFoundation\Response;

abstract class Action
{
    /**
     * @var Request|null
     */
    protected ?Request $request = null;

    /**
     * @return Response
     * @throws BindingResolutionException
     */
    abstract public function action(): Response;

    /**
     * @return IlluminateContainer
     */
    protected function getContainer(): IlluminateContainer
    {
        return IlluminateContainer::getInstance();
    }

    /**
     * Get HTTP Request.
     *
     * @return Request
     * @throws BindingResolutionException
     */
    protected function getRequest(): Request
    {
        if ($this->request instanceof Request) {
            return $this->request;
        }

        if ($this->getContainer() instanceof LumenApplication) {
            return $this->request = $this->getContainer()->make(Request::class);
        }

        return $this->request = $this->getContainer()->make('request');
    }

    /**
     * @param  array  $data
     * @param  int  $code
     * @param  string  $message
     * @return JsonResponse
     */
    protected function asJson(array $data = [], int $code = 200, string $message = 'ok'): JsonResponse
    {
        return response()
            ->json(['code' => $code, 'message' => $message, 'data' => $data])
            ->setStatusCode($code);
    }

    /**
     * @return Response
     * @throws BindingResolutionException
     */
    public function __invoke(): Response
    {
        return $this->action();
    }
}
