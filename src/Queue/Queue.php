<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/10/26
 * Time: 10:13.
 */

namespace HughCube\Laravel\AliFC\Queue;

use AlibabaCloud\SDK\FC\V20230330\Models\InvokeFunctionHeaders;
use AlibabaCloud\SDK\FC\V20230330\Models\InvokeFunctionRequest;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;
use Carbon\Carbon;
use DateInterval;
use DateTimeInterface;
use Exception;
use HughCube\Laravel\AliFC\Client;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue as IlluminateQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Queue extends IlluminateQueue implements QueueContract
{
    /**
     * The client name.
     *
     * @var Client
     */
    protected $client;

    /**
     * The function name.
     *
     * @var string
     */
    protected $function;

    /**
     * The function qualifier.
     *
     * @var ?string
     */
    protected $qualifier;

    /**
     * Create a new fc queue instance.
     */
    public function __construct(
        Client $client,
        string $function,
        ?string $qualifier = null,
        bool $dispatchAfterCommit = false
    ) {
        $this->client = $client;
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
     *
     * @throws Exception
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queue, $data),
            $queue,
            null,
            function ($payload, $queue) {
                return $this->invokeFc($payload);
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
     *
     * @throws Exception
     */
    public function pushRaw($payload, $queue = null, array $options = []): string
    {
        return $this->invokeFc($payload);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  int|DateInterval|DateTimeInterface  $delay
     * @param  object|string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     *
     * @throws Exception
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $queue, $data),
            $queue,
            $delay,
            function ($payload, $queue, $delay) {
                return $this->invokeFc($payload, $delay);
            }
        );
    }

    /**
     * @param  string  $payload
     * @param  int|DateInterval|DateTimeInterface  $delay
     *
     * @return string
     * @throws Exception
     */
    protected function invokeFc(string $payload, $delay = 0): string
    {
        $response = $this->client->invokeFunctionWithOptions(
            $this->function,

            new InvokeFunctionRequest([
                'body' => $payload,
                'qualifier' => $this->qualifier,
            ]),

            new InvokeFunctionHeaders([
                'xFcInvocationType' => 'Async',
                'commonHeaders' => [
                    'X-Fc-Async-Delay' => $this->parseDelay($delay),
                ],
            ]),

            new RuntimeOptions()
        );

        /** 获取请求ID */
        if (empty($requestId = Collection::make($response->headers['X-Fc-Request-Id'])->first() ?: null)) {
            throw new Exception('Description Failed to invoke the fc service.');
        }

        if (300 > $response->statusCode && 200 <= $response->statusCode) {
            return $requestId;
        }

        throw new Exception(sprintf('Description Failed to invoke the fc service, RequestId:%s', $requestId));
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     */
    public function pop($queue = null): ?JobContract
    {
        return null;
    }

    /**
     * Delete all the jobs from the queue.
     */
    public function clear(string $queue): int
    {
        return 0;
    }

    /**
     * Get a random ID string.
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
     */
    protected function createPayloadArray($job, $queue, $data = ''): array
    {
        return array_merge(parent::createPayloadArray($job, $queue, $data), [
            'createdAt' => Carbon::now()->toISOString(true),
        ]);
    }

    /**
     * @param  int|DateInterval|DateTimeInterface  $delay
     * @throws Exception
     */
    protected function parseDelay($delay = 0): int
    {
        if ($delay instanceof DateTimeInterface) {
            return $delay->getTimestamp() - time();
        }

        if ($delay instanceof DateInterval) {
            return $delay->s;
        }

        return intval(max($delay, 0));
    }
}
