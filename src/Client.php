<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/23
 * Time: 11:20
 */

namespace HughCube\Laravel\AliFC;

use AliyunFC\Client as FCClient;
use HughCube\Laravel\AlibabaCloud\AlibabaCloud;
use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class Client extends FCClient
{
    /**
     * @var array 阿里云的配置
     */
    protected $config;

    /**
     * Client constructor.
     * @param array|string $config
     */
    public function __construct($config)
    {
        $this->config = $this->formatConfig($config);

        parent::__construct(array_merge($config, [
            'endpoint' => $this->getEndpoint(),
            'accessKeyID' => $this->getAccessKeyId(),
            'accessKeySecret' => $this->getAccessKeySecret(),
            'securityToken' => $this->getSecurityToken(),
        ]));
    }

    /**
     * @param array|string $config
     * @return mixed|string[]
     */
    protected function formatConfig($config)
    {
        if (is_string($config)) {
            $config = ['alibabaCloud' => $config];
        }

        $alibabaCloud = null;
        if (Arr::has($config, 'alibabaCloud')) {
            $alibabaCloud = AlibabaCloud::client($config["alibabaCloud"]);
        }

        /** AccessKeyID */
        if (empty($config['AccessKeyID']) && null !== $alibabaCloud) {
            $config['AccessKeyID'] = $alibabaCloud->getAccessKeyId();
        }

        /** AccessKeySecret */
        if (empty($config['AccessKeySecret']) && null !== $alibabaCloud) {
            $config['AccessKeySecret'] = $alibabaCloud->getAccessKeySecret();
        }

        /** RegionId */
        if (empty($config['RegionId']) && null !== $alibabaCloud) {
            $config['RegionId'] = $alibabaCloud->getRegionId();
        }

        /** AccountId */
        if (empty($config['AccountId']) && null !== $alibabaCloud) {
            $config['AccountId'] = $alibabaCloud->getAccountId();
        }

        return $config;
    }

    /**
     * @return string|null
     */
    public function getAccessKeyId()
    {
        return Arr::get($this->config, 'AccessKeyID');
    }

    /**
     * @return string|null
     */
    public function getAccessKeySecret()
    {
        return Arr::get($this->config, 'AccessKeySecret');
    }

    /**
     * @return string|null
     */
    public function getSecurityToken()
    {
        return Arr::get($this->config, 'SecurityToken');
    }

    /**
     * @return string|null
     */
    public function getRegionId()
    {
        return Arr::get($this->config, 'RegionId');
    }

    /**
     * @return string|null
     */
    public function getAccountId()
    {
        return Arr::get($this->config, 'AccountId');
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return Arr::get($this->config, 'Options', []);
    }

    /**
     * @return string
     */
    protected function getEndpoint()
    {
        $endpoint = empty($this->config["internal"]) ? "%s.%s.fc.aliyuncs.com" : "%s.%s-internal.fc.aliyuncs.com";
        return sprintf($endpoint, $this->getAccountId(), $this->getRegionId());
    }

    /**
     * @return static
     */
    public function with($config)
    {
        $class = static::class;

        return new $class(array_merge($this->config, $config));
    }

    /**
     * 变更所在地区.
     *
     * @param string $regionId
     * @return static
     */
    public function withRegionId($regionId)
    {
        return $this->with(['AccountId' => $regionId]);
    }

    /**
     * 变更Options.
     *
     * @param array $options
     * @return static
     */
    public function withOptions(array $options)
    {
        return $this->with(['Options' => $options]);
    }

    /**
     * 获取版本号
     *
     * @return string
     */
    public function reflectionApiVersion()
    {
        static $reflectionProperty = null;

        if (!$reflectionProperty instanceof ReflectionProperty) {
            $reflection = new ReflectionClass(FCClient::class);
            $reflectionProperty = $reflection->getProperty("apiVersion");
            $reflectionProperty->setAccessible(true);
        }

        return $reflectionProperty->getValue($this);
    }

    /**
     * 构建请求头
     *
     * @param string $method
     * @param string $path
     * @param array $customHeaders
     * @param array|null $unescapedQueries
     * @return array
     */
    public function reflectionBuildCommonHeaders($method, $path, $customHeaders = [], $unescapedQueries = null)
    {
        static $reflectionMethod = null;

        if (!$reflectionMethod instanceof ReflectionMethod) {
            $reflection = new ReflectionClass(FCClient::class);
            $reflectionMethod = $reflection->getMethod("buildCommonHeaders");
            $reflectionMethod->setAccessible(true);
        }

        return $reflectionMethod->invoke($this, $method, $path, $customHeaders, $unescapedQueries);
    }

    /**
     * 构建请求头
     *
     * @param string $method
     * @param string $path
     * @param array $headers
     * @return array
     */
    public function reflectionDoRequest($method, $path, $headers, $data = null, $query = [])
    {
        static $reflectionMethod = null;

        if (!$reflectionMethod instanceof ReflectionMethod) {
            $reflection = new ReflectionClass(FCClient::class);
            $reflectionMethod = $reflection->getMethod("doRequest");
            $reflectionMethod->setAccessible(true);
        }

        return $reflectionMethod->invoke($this, $method, $path, $headers, $data, $query);
    }

    /**
     * 修改自定义域名
     *
     * @see https://help.aliyun.com/document_detail/191168.html?spm=a2c4g.11186623.6.897.2717f301Ol4jjp
     *
     * @param string $domain
     * @param null|array $cert
     * @param null|array $route
     * @param null|string $protocol
     * @return array
     */
    public function updateCustomDomain($domain, $cert = null, $route = null, $protocol = null)
    {
        $method = 'PUT';
        $path = sprintf('/%s/custom-domains/%s', $this->reflectionApiVersion(), $domain);
        $headers = $this->reflectionBuildCommonHeaders($method, $path, []);

        $payload = [];
        $payload["domainName"] = $domain;
        empty($cert) or $payload["certConfig"] = $cert;
        empty($route) or $payload["routeConfig"] = $route;
        empty($protocol) or $payload["protocol"] = $protocol;

        $content = json_encode($payload);
        $headers['content-length'] = strlen($content);
        return $this->reflectionDoRequest($method, $path, $headers, $content);
    }
}
