<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/31
 * Time: 22:39.
 */

namespace HughCube\Laravel\AliFC\Tests\Commands;

use HughCube\Laravel\AliFC\Tests\TestCase;

class JobPayloadCommandTest extends TestCase
{
    /**
     * @return void
     */
    public function testRun()
    {
        $this->artisan('alifc:job-payload', ['job' => Job::class])->assertExitCode(0);
    }
}
