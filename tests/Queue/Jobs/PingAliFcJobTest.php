<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/31
 * Time: 23:16.
 */

namespace HughCube\Laravel\AliFC\Tests\Queue\Jobs;

use HughCube\Laravel\AliFC\Queue\Jobs\PingJob;
use HughCube\Laravel\AliFC\Tests\TestCase;

/**
 * @group authCase
 */
class PingAliFcJobTest extends TestCase
{
    public function testHandle()
    {
        $job = PingJob::new(['url' => env('PING_URL')]);
        $response = $job->handle();

        $this->assertSame(200, $response->getStatusCode());
    }
}
