<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/31
 * Time: 23:16.
 */

namespace HughCube\Laravel\AliFC\Tests\Queue\Jobs;

use HughCube\Laravel\AliFC\Queue\Jobs\PingAliFcJob;
use HughCube\Laravel\AliFC\Tests\TestCase;

/**
 * @group authCase
 */
class PingAliFcJobTest extends TestCase
{
    public function testHandle()
    {
        $job = PingAliFcJob::new(['url' => env('PING_URL')]);
        $response = $job->handle();

        $this->assertSame(200, $response->getStatusCode());
    }
}
