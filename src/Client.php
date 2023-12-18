<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/23
 * Time: 11:20.
 */

namespace HughCube\Laravel\AliFC;

use AlibabaCloud\SDK\FC\V20230330\FC;
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
class Client
{
    use HttpClientTrait;

    /**
     * @var null|FC
     */
    protected $fc = null;

    /**
     * @var null|Config
     */
    protected $config = null;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function withConfig(Config $config): Client
    {
        /** @phpstan-ignore-next-line */
        return new static($config);
    }

    public function getFc(): FC
    {
        if (null === $this->fc) {
            $this->fc = new FC($this->getConfig()->toFcConfig());
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
        $config = $this->getConfig()->getConfig('Http', []);

        $config['handler'] = $handler = HandlerStack::create();

        /** 替换http请求的host头信息 */
        $handler->push(function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (! $request->hasHeader('Host') && ! empty($host = $this->getConfig()->getHost())) {
                    $request = $request->withHeader('Host', $host);
                }

                if (! $request->hasHeader('Host')) {
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
            ['base_uri' => $this->getConfig()->getFcBaseUri()],
            $config
        ));
    }
}
