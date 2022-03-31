<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/31
 * Time: 21:46
 */

namespace HughCube\Laravel\AliFC\Tests\Actions;

use HughCube\Laravel\AliFC\Actions\InvokeAction;
use HughCube\Laravel\AliFC\Manager;
use HughCube\Laravel\AliFC\Queue\Queue;
use HughCube\Laravel\AliFC\Tests\Actions\Job\CacheJob;
use HughCube\Laravel\AliFC\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class InvokeActionTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     * @throws Throwable
     */
    public function testAction(Request $request, $cacheKey)
    {
        $this->app->instance('request', $request);
        $this->getCache()->forget($cacheKey);

        $this->assertNull($this->getCache()->get($cacheKey));
        $action = new InvokeAction();
        $response = $action();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($cacheKey, $this->getCache()->get($cacheKey));
    }

    /**
     * @throws ReflectionException
     */
    public function dataProvider(): array
    {
        $cases = [];
        for ($i = 1; $i <= 10; $i++) {
            $key = Str::random();

            $job = new CacheJob($key);

            $queue = $this->getQueue();
            $reflection = new ReflectionClass(get_class($this->getQueue()));
            $method = $reflection->getMethod('createPayload');
            $payload = $method->invokeArgs($queue, [$job, $queue]);

            $request = Request::create(
                '/invoke',
                'POST',
                [],
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                $payload
            );
            $cases[] = [$request, $key];

            $request = Request::create(
                '/invoke',
                'POST',
                [],
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['payload' => $payload])
            );
            $cases[] = [$request, $key];
        }

        return $cases;
    }

    protected function getQueue(): Queue
    {
        return new Queue(new Manager(), 'default', 'default', 'default');
    }
}
