<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/31
 * Time: 23:04.
 */

namespace HughCube\Laravel\AliFC\Tests\Queue;

use HughCube\Laravel\AliFC\AliFC;
use HughCube\Laravel\AliFC\Queue\Connector;
use HughCube\Laravel\AliFC\Queue\Job;
use HughCube\Laravel\AliFC\Queue\Queue;
use HughCube\Laravel\AliFC\Tests\TestCase;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class QueueTest extends TestCase
{
    public function testConnect()
    {
        $connector = new Connector(AliFC::getFacadeRoot());

        $queue = $connector->connect(['client' => null, 'service' => 'test', 'function' => 'test']);

        $this->assertInstanceOf(QueueContract::class, $queue);
        $this->assertInstanceOf(Queue::class, $queue);

        $job = new Job(IlluminateContainer::getInstance(), '{}');
        $this->assertInstanceOf(JobContract::class, $job);
        $this->assertSame($job->getRawBody(), '{}');
    }
}
