<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2024/1/12
 * Time: 18:55
 */

namespace HughCube\Laravel\AliFC\Actions;

use Illuminate\Container\Container;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Events\Dispatcher as EventsDispatcher;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class Action
{
    /**
     * @var Container|null
     */
    private static $container = null;

    public function __invoke(): Response
    {
        $this->getEventsDispatcher()->dispatch($this, [], true);

        return $this->action();
    }

    protected function action(): Response
    {
        return new JsonResponse(['code' => 200, 'message' => 'ok']);
    }

    protected static function setContainer(Container $container)
    {
        self::$container = $container;
    }

    protected static function getContainer(): Container
    {
        return self::$container ?? IlluminateContainer::getInstance();
    }

    /**
     * @phpstan-ignore-next-line
     * @throws
     */
    protected function getDispatcher(): Dispatcher
    {
        return $this->getContainer()->make(Dispatcher::class);
    }

    /**
     * @phpstan-ignore-next-line
     * @throws
     */
    protected function getEventsDispatcher(): EventsDispatcher
    {
        return $this->getContainer()->make(EventsDispatcher::class);
    }
}
