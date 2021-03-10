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
     * @var FCClient
     */
    protected $client;

    /**
     * @var array 阿里云的配置
     */
    protected $config;

    /**
     * Client constructor.
     * @param array $config
     */
    public function __construct($config)
    {
        /** 尝试通过AlibabaCloud获取配置信息 */
        if (empty($config["accessKey"]) && empty($config["accessKeyID"])) {
            $config = $this->mergeAlibabaCloudConfig($config);
        }

        if (empty($config["accessKey"]) && !empty($config["accessKeyID"])) {
            $config["accessKey"] = $config["accessKeyID"];
        }

        $config["endpoint"] = $this->buildEndpoint($config);

        parent::__construct(($this->config = $config));
    }

    /**
     * @param $config
     * @return array
     */
    protected function mergeAlibabaCloudConfig($config)
    {
        $alibabaCloud = empty($config["alibabaCloud"]) ? null : $config["alibabaCloud"];
        $alibabaCloud = (empty($alibabaCloud) && !is_array($config) && !empty($config)) ? $config : $alibabaCloud;

        $alibabaCloud = is_object($alibabaCloud) ? $alibabaCloud : AlibabaCloud::client($alibabaCloud);

        $config = is_array($config) ? $config : [];
        $config["accessKeyID"] = $alibabaCloud->getAccessKey();
        $config["accessKeySecret"] = $alibabaCloud->getAccessKeySecret();
        $config["regionId"] = $alibabaCloud->getRegionId();
        $config["accountId"] = $alibabaCloud->getAccountId();

        return $config;
    }

    /**
     * @param array $config
     * @return string
     */
    protected function buildEndpoint(array $config)
    {
        $endpoint = empty($config["internal"]) ? "%s.%s.fc.aliyuncs.com" : "%s.%s-internal.fc.aliyuncs.com";

        return sprintf($endpoint, $config["accountId"], $config["regionId"]);
    }

    /**
     * @return string
     */
    public function getAccessKey()
    {
        return Arr::get($this->config, "accessKeyID");
    }

    /**
     * @return string
     */
    public function getAccessKeySecret()
    {
        return Arr::get($this->config, "accessKeySecret");
    }

    /**
     * @return string
     */
    public function getRegionId()
    {
        return Arr::get($this->config, "regionId");
    }

    /**
     * @return string
     */
    public function getAccountId()
    {
        return Arr::get($this->config, "accountId");
    }

    /**
     * 变更所在地区, 主要在账号密码不变更, 切换地区使用
     *
     * @param string $regionId
     * @return static
     */
    public function withRegionId($regionId)
    {
        $config = $this->config;
        $config["regionId"] = $regionId;

        return new static($config);
    }

    /**
     * 获取版本号
     *
     * @return string
     */
    public function reflectionApiVersion()
    {
        static $property = null;

        if (!$property instanceof ReflectionProperty) {
            $reflection = new ReflectionClass($this);
            $property = $reflection->getProperty("apiVersion");
        }

        return $property->getValue($this);
    }

    /**
     * 构建请求头
     *
     * @param $method
     * @param $path
     * @param $headers
     * @return array
     */
    public function reflectionBuildCommonHeaders($methodType, $path, $customHeaders = [], $unescapedQueries = null)
    {
        static $method = null;

        if (!$method instanceof ReflectionMethod) {
            $reflection = new ReflectionClass($this);
            $method = $reflection->getMethod("buildCommonHeaders");
        }

        return $method->invoke($this, $methodType, $path, $customHeaders, $unescapedQueries);
    }

    /**
     * 构建请求头
     *
     * @param $method
     * @param $path
     * @param $headers
     * @return array
     */
    public function reflectionDoRequest($method, $path, $headers, $data = null, $query = [])
    {
        static $method = null;

        if (!$method instanceof ReflectionMethod) {
            $reflection = new ReflectionClass($this);
            $method = $reflection->getMethod("doRequest");
        }

        return $method->invoke($this, $method, $path, $headers, $data, $query);
    }

    /**
     * 修改自定义域名
     *
     * @see https://help.aliyun.com/document_detail/191168.html?spm=a2c4g.11186623.6.897.2717f301Ol4jjp
     *
     * @param $domain
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
