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
use HughCube\GuzzleHttp\Middleware\AutoSkipVerifyMiddleware;
use HughCube\GuzzleHttp\Middleware\UseHostResolveMiddleware;
use HughCube\Laravel\AliFC\Config\Config;
use HughCube\Laravel\AliFC\Util\OpenApiUtil;

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
            /** @deprecated  */
            $uri = strtr($uri, ['{{fcApiVersion}}' => '{{version}}']);

            $uri = strtr($uri, ['{{version}}' => $options[RequestOptions::HEADERS]['X-Acs-Version']]);
        }

        /** fcApi调用 */
        $options['extra']['is_alifc_api'] = true;

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

        /** fc签名 */
        $handler->push(function (callable $handler) {
            return OpenApiUtil::fcApiSignatureRequestMiddleware($this, $handler);
        });

        /** 自定义host解析 */
        $handler->push(function (callable $handler) {
            return UseHostResolveMiddleware::middleware($handler);
        });

        /** 证书认证 */
        $handler->push(function (callable $handler) {
            return AutoSkipVerifyMiddleware::middleware($handler);
        });

        return new HttpClient(array_merge(
            ['base_uri' => $this->getConfig()->getFcBaseUri()],
            $config
        ));
    }
}
