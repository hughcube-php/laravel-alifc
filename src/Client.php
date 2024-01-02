<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/23
 * Time: 11:20.
 */

namespace HughCube\Laravel\AliFC;

use AlibabaCloud\SDK\FC\V20230330\FC;
use Darabonba\OpenApi\Models\Config as FcConfig;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use HughCube\GuzzleHttp\Client as HttpClient;
use HughCube\GuzzleHttp\HttpClientTrait;
use HughCube\GuzzleHttp\LazyResponse;
use HughCube\Laravel\AliFC\Config\Config;
use Psr\Http\Message\RequestInterface;

/**
 * @mixin FC
 */
class Client extends Config
{
    use HttpClientTrait;

    /**
     * @var null|FC
     */
    protected $fc = null;

    public function getFc(): FC
    {
        if (null === $this->fc) {
            $this->fc = new FC(new FcConfig([
                'accessKeyId' => $this->getAccessKeyId(),
                'accessKeySecret' => $this->getAccessKeySecret(),
                'securityToken' => $this->getSecurityToken(),
                'protocol' => $this->getScheme(),
                'regionId' => $this->getRegionId(),
                'endpoint' => $this->getEndpoint(),
                'type' => $this->getType(),
            ]));
        }
        return $this->fc;
    }

    public function __call($name, $arguments)
    {
        return $this->getFc()->{$name}(...$arguments);
    }

    public function request(string $method, $uri, array $options = []): LazyResponse
    {
        return $this->getHttpClient()->requestLazy(strtoupper($method), $uri, $options);
    }

    protected function createHttpClient(): HttpClient
    {
        $config = $this->getConfig('Http', []);

        $config['handler'] = $handler = HandlerStack::create();

        /** 替换http请求的host头信息 */
        $handler->push(function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (!$request->hasHeader('Host') && !empty($host = $this->getHost())) {
                    $request = $request->withHeader('Host', $host);
                }

                if (!$request->hasHeader('Host')) {
                    $request = $request->withHeader('Host', $request->getUri()->getHost());
                }

                /** When you forcibly change the host using HTTPS, HTTPS authentication must be disabled. */
                if ('https' === $request->getUri()->getScheme()
                    && $request->getUri()->getHost() !== $request->getHeaderLine('Host')
                ) {
                    $options[RequestOptions::VERIFY] = false;
                }

                return $handler($request, $options);
            };
        });

        return new HttpClient(array_merge(
            ['base_uri' => $this->getFcBaseUri()],
            $config
        ));
    }
}
