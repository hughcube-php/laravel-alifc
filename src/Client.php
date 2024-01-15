<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/23
 * Time: 11:20.
 */

namespace HughCube\Laravel\AliFC;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use HughCube\GuzzleHttp\Client as HttpClient;
use HughCube\GuzzleHttp\HttpClientTrait;
use HughCube\GuzzleHttp\LazyResponse;
use HughCube\Laravel\AliFC\Config\Config;
use HughCube\Laravel\AliFC\Util\OpenApiUtil;
use Psr\Http\Message\RequestInterface;

class Client
{
    use HttpClientTrait;

    /**
     * @var Config
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

    public function withRegionId(string $regionId): Client
    {
        /** @phpstan-ignore-next-line */
        return new static($this->getConfig()->withRegionId($regionId));
    }

    public function request(string $method, $uri, array $options = []): LazyResponse
    {
        return $this->getHttpClient()->requestLazy(strtoupper($method), $uri, $options);
    }

    public function fcApi(string $method, $uri, array $options = []): LazyResponse
    {
        /** 设置版本号 */
        $options[RequestOptions::HEADERS]['X-Acs-Version'] = $this->getConfig()->getVersion();
        if (is_string($uri)) {
            $uri = strtr($uri, ['{{fcApiVersion}}' => $options[RequestOptions::HEADERS]['X-Acs-Version']]);
        }

        /** fcApi调用 */
        $options['fcApi'] = true;

        return $this->request($method, $uri, $options);
    }

    protected function createHttpClient(): HttpClient
    {
        $config = $this->getConfig()->get('Http', []);

        $config['handler'] = $handler = HandlerStack::create();

        /** 补齐请求的信息 */
        $handler->push(function (callable $handler) {
            return OpenApiUtil::completeRequestMiddleware($this, $handler);
        });

        /** fcApi签名 */
        $handler->push(function (callable $handler) {
            return OpenApiUtil::fcApiSignatureRequestMiddleware($this, $handler);
        });

        /** 证书认证 */
        $handler->push(function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
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
