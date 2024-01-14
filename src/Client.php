<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/23
 * Time: 11:20.
 */

namespace HughCube\Laravel\AliFC;

use AlibabaCloud\SDK\FC\V20230330\FC as FcClient;
use Darabonba\OpenApi\Models\Config as FcConfig;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use HughCube\GuzzleHttp\Client as HttpClient;
use HughCube\GuzzleHttp\HttpClientTrait;
use HughCube\GuzzleHttp\LazyResponse;
use HughCube\Laravel\AliFC\Config\Config;
use Psr\Http\Message\RequestInterface;

/**
 * @mixin FcClient
 */
class Client
{
    use HttpClientTrait;

    /**
     * @var Config
     */
    protected $config = null;

    /**
     * @var null|FcClient
     */
    protected $fcClient = null;

    public function __construct(Config $config)
    {
        $this->config = $config;

        $this->fcClient = new FcClient(new FcConfig([
            'accessKeyId' => $this->getConfig()->getAccessKeyId(),
            'accessKeySecret' => $this->getConfig()->getAccessKeySecret(),
            'securityToken' => $this->getConfig()->getSecurityToken(),
            'protocol' => $this->getConfig()->getScheme(),
            'regionId' => $this->getConfig()->getRegionId(),
            'endpoint' => $this->getConfig()->getEndpoint(),
            'type' => $this->getConfig()->getType(),
        ]));
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getFcClient(): FcClient
    {
        return $this->fcClient;
    }

    public function __call($name, $arguments)
    {
        return $this->getFcClient()->{$name}(...$arguments);
    }

    public function request(string $method, $uri, array $options = []): LazyResponse
    {
        return $this->getHttpClient()->requestLazy(strtoupper($method), $uri, $options);
    }

    protected function createHttpClient(): HttpClient
    {
        $config = $this->getConfig()->get('Http', []);

        $config['handler'] = $handler = HandlerStack::create();

        /** 替换http请求的host头信息 */
        $handler->push(function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (!$request->hasHeader('Host') && !empty($host = $this->getConfig()->getHost())) {
                    $request = $request->withHeader('Host', $host);
                }

                if (!$request->hasHeader('Date')) {
                    $request = $request->withHeader('Date', gmdate('D, d M Y H:i:s T'));
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

        return new HttpClient(array_merge(['base_uri' => $this->getConfig()->getFcBaseUri()], $config));
    }
}
