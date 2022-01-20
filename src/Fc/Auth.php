<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/9/22
 * Time: 10:04
 */

namespace HughCube\Laravel\AliFC\Fc;

use Closure;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;

class Auth
{
    /**
     * @var HttpClient|null
     */
    private ?HttpClient $httpClient = null;

    /**
     * @var array 阿里云的配置
     */
    private array $config;

    /**
     * @param  array  $config
     */
    public function __construct(array $config)
    {
        $this->config = array_replace_recursive([
            'Http' => [
                RequestOptions::TIMEOUT => 10.0,
                RequestOptions::CONNECT_TIMEOUT => 10.0,
                RequestOptions::READ_TIMEOUT => 10.0,
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::HEADERS => []

            ],
        ], $config);
    }

    public function getConfig(string $key, $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }

    public function getApiVersion()
    {
        return $this->getConfig('ApiVersion', '2016-08-15');
    }

    public function getAccessKeyId(): ?string
    {
        return $this->getConfig('AccessKeyID');
    }

    public function getAccessKeySecret(): ?string
    {
        return $this->getConfig('AccessKeySecret');
    }

    public function getSecurityToken(): ?string
    {
        return $this->getConfig('SecurityToken');
    }

    public function getRegionId(): ?string
    {
        return $this->getConfig('RegionId');
    }

    public function getAccountId(): ?string
    {
        return $this->getConfig('AccountId');
    }

    public function isInternal(): bool
    {
        return true == $this->getConfig('Internal');
    }

    public function getServiceIp(): ?string
    {
        return $this->getConfig('ServiceIp');
    }

    /**
     * @return string
     */
    protected function getEndpoint(): string
    {
        $endpoint = $this->isInternal() ? '%s.%s-internal.fc.aliyuncs.com' : '%s.%s.fc.aliyuncs.com';
        return sprintf($endpoint, $this->getAccountId(), $this->getRegionId());
    }

    /**
     * @param  array  $config
     * @return static
     */
    public function with(array $config): static
    {
        $class = static::class;
        return new $class(array_merge($this->config, $config));
    }

    /**
     * 变更所在地区.
     *
     * @param  string  $regionId
     * @return static
     */
    public function withRegionId(string $regionId): static
    {
        return $this->with(['RegionId' => $regionId]);
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient(): HttpClient
    {
        if (!$this->httpClient instanceof HttpClient) {
            $config = $this->getConfig('Http', []);

            $config['handler'] = $handler = HandlerStack::create();
            $handler->push($this->signHandler());

            $this->httpClient = new HttpClient(array_merge([
                'base_uri' => sprintf("http://%s", $this->getEndpoint())
            ], $config));
        }

        return $this->httpClient;
    }

    /**
     * 添加请求头信息.
     *
     * @return Closure
     */
    private function signHandler(): Closure
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if (!$request->hasHeader('Host')) {
                    $request = $request->withHeader('Host', $request->getUri()->getHost());
                }

                if (!$request->hasHeader('Date')) {
                    $request = $request->withHeader('Date', gmdate('D, d M Y H:i:s T'));
                }

                if (!$request->hasHeader('Content-Type')) {
                    $request = $request->withHeader('Content-Type', 'application/json');
                }

                if (!$request->hasHeader('Content-Length')) {
                    $request = $request->withHeader('Content-Length', 0);
                }

                $data = implode("\n", [
                    strtoupper($request->getMethod()),
                    $request->getHeaderLine('Content-md5'),
                    $request->getHeaderLine('Content-type'),
                    $request->getHeaderLine('Date'),
                    $this->implodeFcHeaders($request).$this->implodeFcResource($request)
                ]);

                $hash = hash_hmac('sha256', $data, $this->getAccessKeySecret(), true);
                $signature = sprintf('FC %s:%s', $this->getAccessKeyId(), base64_encode($hash));
                $request = $request->withHeader('Authorization', $signature);

                if (!empty($ip = $this->getServiceIp())) {
                    $host = $request->getUri()->getHost();
                    $request = $request->withUri($request->getUri()->withHost($ip))->withHeader('Host', $host);
                }

                return $handler($request, $options);
            };
        };
    }

    /**
     * @param  RequestInterface  $request
     * @return string
     */
    private function implodeFcHeaders(RequestInterface $request): string
    {
        $canonicalHeaders = [];
        foreach ($request->getHeaders() as $name => $values) {
            $lowerName = strtolower($name);
            if (!Str::startsWith($lowerName, 'x-fc-')) {
                continue;
            }

            foreach ($values as $value) {
                $canonicalHeaders[$lowerName] = $value;
            }
        }
        ksort($canonicalHeaders);

        $canonical = '';
        foreach ($canonicalHeaders as $name => $value) {
            $canonical = $canonical.$name.':'.$value."\n";
        }
        return $canonical;
    }

    /**
     * @param  RequestInterface  $request
     * @return string
     */
    private function implodeFcResource(RequestInterface $request): string
    {
        $queryArray = [];
        if (!empty($query = $request->getUri()->getQuery())) {
            parse_str($query, $queryArray);
        }
        ksort($queryArray);

        $params = [];
        foreach ($queryArray as $name => $values) {
            foreach ((array) $values as $value) {
                $params[] = sprintf('%s=%s', $name, $value);
            }
        }

        $resource = Util::unescape($request->getUri()->getPath());

        if (!empty($params)) {
            $resource .= ("\n".implode("\n", $params));
        }//
        /** 如果host不相等, 判断为http触发器处理的请求 */
        elseif ($request->getUri()->getHost() !== $this->getEndpoint()) {
            $resource .= "\n";
        }

        return $resource;
    }
}
