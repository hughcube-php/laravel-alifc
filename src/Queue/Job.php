<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/10/26
 * Time: 10:13.
 */

namespace HughCube\Laravel\AliFC\Queue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;

class Job extends \Illuminate\Queue\Jobs\Job implements JobContract
{
    /**
     * The alifc raw job payload.
     *
     * @var string
     */
    protected $job;

    /**
     * The JSON decoded version of "$job".
     *
     * @var array
     */
    protected $decoded;

    /**
     * Create a new job instance.
     *
     * @param  Container  $container
     * @param  string  $job
     * @param  string  $connectionName
     * @param  string  $queue
     */
    public function __construct(Container $container, string $job, string $connectionName = 'alifc', string $queue = 'default')
    {
        $this->container = $container;
        $this->job = $job;
        $this->connectionName = $connectionName;
        $this->queue = $queue;
        $this->decoded = $this->payload();
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody(): string
    {
        return $this->job;
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int
    {
        return ($this->decoded['attempts'] ?? 0) + 1;
    }

    /**
     * Get the job identifier.
     *
     * @return string|null
     */
    public function getJobId(): ?string
    {
        return $this->decoded['uuid'] ?? null;
    }
}
