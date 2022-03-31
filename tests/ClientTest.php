<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/15
 * Time: 7:14 下午.
 */

namespace HughCube\Laravel\AliFC\Tests;

use HughCube\Laravel\AliFC\AliFC;
use HughCube\Laravel\AliFC\Client;
use Illuminate\Support\Arr;
use Throwable;

/**
 * @group authCase
 */
class ClientTest extends TestCase
{
    public function testInstanceOf()
    {
        $this->assertInstanceOf(Client::class, AliFC::client());
    }

    /**
     * @throws Throwable
     */
    public function testClient()
    {
        $this->assertClient(
            AliFC::client(),
            config(sprintf('alifc.clients.%s', config('alifc.default')))
        );

        foreach (config('alifc.clients') as $name => $value) {
            $this->assertClient(AliFC::client($name), $value);
        }
    }

    /**
     * @throws Throwable
     */
    protected function assertClient(Client $client, $config)
    {
        $this->assertSame(Arr::get($config, 'AccessKeyID'), $client->getAccessKeyId());
        $this->assertSame(Arr::get($config, 'AccessKeySecret'), $client->getAccessKeySecret());
        $this->assertSame(Arr::get($config, 'RegionId'), $client->getRegionId());
        $this->assertSame(Arr::get($config, 'AccountId'), $client->getAccountId());

        $regionId = md5(random_bytes(100));
        $this->assertSame($client->withRegionId($regionId)->getRegionId(), $regionId);

        $response = $client->invoke('tbk', 'schedule');
        $this->assertJson($response->getBody()->getContents());

        $response = AliFC::invoke('tbk', 'schedule');
        $this->assertJson($response->getBody()->getContents());
    }
}
