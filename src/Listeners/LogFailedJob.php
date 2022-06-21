<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/21
 * Time: 18:06
 */

namespace HughCube\Laravel\AliFC\Listeners;

use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

class LogFailedJob
{
    /**
     * @throws BindingResolutionException
     */
    public function handle(JobFailed $event): void
    {
        $this->getQueueFailer()->log(
            $event->connectionName,
            $event->job->getQueue(),
            $event->job->getRawBody(),
            $event->exception
        );
    }

    /**
     * @return IlluminateContainer
     */
    protected function getContainer(): IlluminateContainer
    {
        return IlluminateContainer::getInstance();
    }

    /**
     * @return FailedJobProviderInterface
     *
     * @throws BindingResolutionException
     */
    protected function getQueueFailer(): FailedJobProviderInterface
    {
        return $this->getContainer()->make('queue.failer');
    }
}
