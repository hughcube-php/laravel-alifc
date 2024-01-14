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
use HughCube\Laravel\AliFC\Util\OpenApiUtil;

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
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function withRegionId(string $regionId): Config
    {
        /** @phpstan-ignore-next-line */
        return new static($this->getConfig()->withRegionId($regionId));
    }

    public function getFcClient(): FcClient
    {
        if (null === $this->fcClient) {
            $this->fcClient = new FcClient(new FcConfig([
                'accessKeyId' => $this->getConfig()->getAccessKeyId(),
                'accessKeySecret' => $this->getConfig()->getAccessKeySecret(),
                'securityToken' => $this->getConfig()->getSecurityToken(),
                'protocol' => $this->getConfig()->getScheme(),
                'regionId' => $this->getConfig()->getRegionId(),
                'endpoint' => $this->getConfig()->getEndpoint(),
                'type' => $this->getConfig()->getType(),
                'httpProxy' => 'http://host.docker.internal:8888',
                'httpsProxy' => 'http://host.docker.internal:8888',
            ]));
        }

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

        /** 替换http请求的host头信息 */
        $handler->push(function (callable $handler) {
            return OpenApiUtil::completeRequestMiddleware($this, $handler);
        });

        /** fcApi签名 */
        $handler->push(function (callable $handler) {
            return OpenApiUtil::fcApiSignatureRequestMiddleware($this, $handler);
        });

        return new HttpClient(array_merge(['base_uri' => $this->getConfig()->getFcBaseUri()], $config));
    }
}
