<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/10/26
 * Time: 10:13
 */

namespace HughCube\Laravel\AliFC\Queue;

use Illuminate\Container\Container;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use JetBrains\PhpStorm\Pure;
use Throwable;

class Process
{
    protected ?Container $container;

    protected ?string $content;

    protected ?string $connectionName;

    /**
     * @throws BindingResolutionException
     * @throws Throwable
     */
    public function run()
    {
        $job = $this->getJob();
        $options = $this->getWorkerOptions();
        $connectionName = $this->getConnectionName();

        try {
            $this->getWorker()->process($connectionName, $job, $options);
        } catch (Throwable $exception) {
            $this->getFailer()->log($connectionName, 'default', $job->getRawBody(), $exception);

            throw $exception;
        }
    }

    /**
     * @param  string  $content
     * @return $this
     */
    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param  string  $name
     * @return $this
     */
    public function setConnectionName(string $name): static
    {
        $this->connectionName = $name;
        return $this;
    }

    protected function getConnectionName(): string
    {
        return $this->connectionName;
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        if (is_callable($this->container)) {
            return call_user_func($this->container);
        }

        if (null === $this->container) {
            return IlluminateContainer::getInstance();
        }

        return $this->container;
    }

    /**
     * @return Job
     */
    protected function getJob(): Job
    {
        return new Job($this->getContainer(), $this->content, $this->getConnectionName());
    }

    /**
     * @return FailedJobProviderInterface
     * @throws BindingResolutionException
     */
    protected function getFailer(): FailedJobProviderInterface
    {
        return $this->getContainer()->make('queue.failer');
    }

    /**
     * @return Worker
     * @throws BindingResolutionException
     */
    protected function getWorker(): Worker
    {
        return $this->getContainer()->make('queue.worker');
    }

    #[Pure]
    protected function getWorkerOptions(): WorkerOptions
    {
        return new WorkerOptions();
    }
}
