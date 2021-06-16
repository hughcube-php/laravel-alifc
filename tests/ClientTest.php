<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/15
 * Time: 7:14 下午.
 */

namespace HughCube\Laravel\AliFC\Tests;

use AliyunFC\Client as FCClient;
use HughCube\Laravel\AlibabaCloud\AlibabaCloud;
use HughCube\Laravel\AliFC\AliFC;
use HughCube\Laravel\AliFC\Client;
use Illuminate\Support\Arr;

class ClientTest extends TestCase
{
    public function testInstanceOf()
    {
        $this->assertInstanceOf(Client::class, AliFC::client());
        $this->assertInstanceOf(FCClient::class, AliFC::client());
    }

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

    protected function assertClient(Client $client, $config)
    {
        if (Arr::has($config, 'alibabaCloud')) {
            $alibabaCloud = AlibabaCloud::client(Arr::get($config, 'alibabaCloud'));
            $this->assertSame($alibabaCloud->getAccessKeyId(), $client->getAccessKeyId());
            $this->assertSame($alibabaCloud->getAccessKeySecret(), $client->getAccessKeySecret());
            $this->assertSame($alibabaCloud->getRegionId(), $client->getRegionId());
            $this->assertSame($alibabaCloud->getAccountId(), $client->getAccountId());
        } else {
            $this->assertSame(Arr::get($config, 'AccessKeyID'), $client->getAccessKeyId());
            $this->assertSame(Arr::get($config, 'AccessKeySecret'), $client->getAccessKeySecret());
            $this->assertSame(Arr::get($config, 'RegionId'), $client->getRegionId());
            $this->assertSame(Arr::get($config, 'AccountId'), $client->getAccountId());
        }
    }
}
