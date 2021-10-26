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
    protected ?Container $container = null;

    protected ?string $content = null;

    protected ?string $connectionName = null;

    protected ?Job $job = null;

    public static function instance(): static
    {
        $class = static::class;
        return new $class();
    }

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

        return $job->getJobId();
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
     * @return string
     */
    protected function getContent(): string
    {
        return $this->content;
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
    protected function getContainer(): Container
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
    public function getJob(): Job
    {
        if (!$this->job instanceof Job) {
            $this->job = new Job($this->getContainer(), $this->getContent(), $this->getConnectionName());
        }

        return $this->job;
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
