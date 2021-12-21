<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/10/26
 * Time: 10:13
 */

namespace HughCube\Laravel\AliFC\Queue;

use Carbon\Carbon;
use DateInterval;
use DateTimeInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use HughCube\Laravel\AliFC\Client;
use HughCube\Laravel\AliFC\Manager as Fc;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue as IlluminateQueue;
use Illuminate\Support\Str;

class Queue extends IlluminateQueue implements QueueContract, ClearableQueue
{
    /**
     * The fc factory implementation.
     *
     * @var Fc
     */
    protected Fc $fc;

    /**
     * The client name.
     *
     * @var null|string
     */
    protected ?string $client;

    /**
     * The service name.
     *
     * @var string
     */
    protected string $service;

    /**
     * The function name.
     *
     * @var string
     */
    protected string $function;

    /**
     * The function qualifier.
     *
     * @var ?string
     */
    protected ?string $qualifier;

    /**
     * Create a new fc queue instance.
     *
     * @param  Fc  $fc
     * @param  ?string  $client
     * @param  string  $service
     * @param  string  $function
     * @param  string|null  $qualifier
     * @param  bool  $dispatchAfterCommit
     */
    public function __construct(
        Fc $fc,
        ?string $client,
        string $service,
        string $function,
        ?string $qualifier = null,
        bool $dispatchAfterCommit = false
    ) {
        $this->fc = $fc;
        $this->client = $client;
        $this->service = $service;
        $this->function = $function;
        $this->qualifier = $qualifier;
        $this->dispatchAfterCommit = $dispatchAfterCommit;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function size($queue = null): int
    {
        return 0;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  object|string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     * @throws Exception|GuzzleException
     */
    public function push($job, $data = '', $queue = null): mixed
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queue, $data),
            $queue,
            null,
            function ($payload, $queue) {
                return $this->pushRaw($payload, $queue);
            }
        );
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array  $options
     * @return string
     * @throws GuzzleException
     * @throws Exception
     */
    public function pushRaw($payload, $queue = null, array $options = []): string
    {
        $response = $this->getClient()->invoke(
            $this->service,
            $this->function,
            $this->qualifier,
            $payload,
            ['type' => 'Async']
        );

        $requestId = $response->getHeaderLine('X-Fc-Request-Id');
        if (empty($requestId)) {
            throw new Exception('The function failed to calculate the service response.');
        }

        if (300 > $response->getStatusCode() && 200 <= $response->getStatusCode()) {
            return $requestId;
        }

        throw new Exception(sprintf('The function calculation call failed, RequestId:%s', $requestId));
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  DateTimeInterface|DateInterval|int  $delay
     * @param  object|string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     * @throws GuzzleException
     */
    public function later($delay, $job, $data = '', $queue = null): mixed
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null): ?\Illuminate\Contracts\Queue\Job
    {
        return null;
    }

    /**
     * Delete all the jobs from the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function clear($queue): int
    {
        return 0;
    }

    /**
     * Get a random ID string.
     *
     * @return string
     */
    protected function getRandomId(): string
    {
        return Str::random(32);
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  string  $queue
     * @param  mixed  $data
     * @return array
     */
    protected function createPayloadArray($job, $queue, $data = ''): array
    {
        return array_merge(parent::createPayloadArray($job, $queue, $data), [
            'createdAt' => Carbon::now()->toISOString(true)
        ]);
    }

    /**
     * Get the connection for the queue.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->getFc()->client($this->client);
    }

    /**
     * Get the underlying fc instance.
     *
     * @return Fc
     */
    public function getFc(): Fc
    {
        return $this->fc;
    }
}
