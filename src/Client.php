<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/23
 * Time: 11:20.
 */

namespace HughCube\Laravel\AliFC;

use GuzzleHttp\RequestOptions;
use HughCube\GuzzleHttp\LazyResponse;
use HughCube\Laravel\AliFC\Fc\Auth;

class Client extends Auth
{
    public function request(string $method, $uri, array $options = []): LazyResponse
    {
        return $this->getHttpClient()->requestLazy(strtoupper($method), $uri, $options);
    }

    /**
     * @param  string  $service
     * @param  string  $function
     * @param  string|null  $qualifier
     * @param  string|null  $payload
     * @param  array  $options
     * @return LazyResponse
     */
    public function invoke(
        string $service,
        string $function,
        ?string $qualifier = null,
        ?string $payload = null,
        array $options = []
    ): LazyResponse {
        $service = empty($qualifier) ? $service : "$service.$qualifier";
        $path = sprintf('/%s/services/%s/functions/%s/invocations', $this->getApiVersion(), $service, $function);

        $options[RequestOptions::BODY] = $payload;
        $options[RequestOptions::HEADERS]['X-Fc-Invocation-Type'] = $options['type'] ?? 'Sync';
        $options[RequestOptions::HEADERS]['X-Fc-Log-Type'] = $options['log'] ?? 'None';

        if (($delay = $options['delay'] ?? 0) > 0) {
            $options[RequestOptions::HEADERS]['X-Fc-Async-Delay'] = $delay;
        }

        if (!empty($invokeId = $options['id'] ?? null)) {
            $options[RequestOptions::HEADERS]['X-Fc-Stateful-Async-Invocation-Id'] = $invokeId;
        }

        return $this->request('POST', $path, $options);
    }

    /**
     * @param  string  $name
     * @param  string  $description
     * @param  array  $options
     * @return LazyResponse
     *
     * @see https://help.aliyun.com/document_detail/175256.html
     */
    public function createService(string $name, string $description = '', array $options = []): LazyResponse
    {
        $path = sprintf('/%s/services', $this->getApiVersion());
        $options[RequestOptions::JSON]['serviceName'] = $name;
        $options[RequestOptions::JSON]['description'] = $description;

        return $this->request('POST', $path, $options);
    }

    /**
     * @param  string  $name
     * @param  string|null  $qualifier
     * @param  array  $options
     * @return LazyResponse
     *
     * @see https://help.aliyun.com/document_detail/189225.html
     */
    public function getService(string $name, ?string $qualifier = null, array $options = []): LazyResponse
    {
        $name = empty($qualifier) ? $name : "$name.$qualifier";
        $path = sprintf('/%s/services/%s', $this->getApiVersion(), $name);

        return $this->request('GET', $path, $options);
    }

    public function updateCustomDomain(
        string $domain,
        ?array $cert = null,
        ?array $route = null,
        string $protocol = null,
        array $options = []
    ): LazyResponse {
        $path = sprintf('/%s/custom-domains/%s', $this->getApiVersion(), $domain);

        $options[RequestOptions::JSON]['domainName'] = $domain;

        if (!empty($cert)) {
            $options[RequestOptions::JSON]['certConfig'] = $cert;
        }

        if (!empty($route)) {
            $options[RequestOptions::JSON]['routeConfig'] = $route;
        }

        if (!empty($protocol)) {
            $options[RequestOptions::JSON]['protocol'] = $protocol;
        }

        return $this->request('PUT', $path, $options);
    }

    public function getCustomDomain(string $domain, array $options = []): LazyResponse
    {
        $path = sprintf('/%s/custom-domains/%s', $this->getApiVersion(), $domain);

        return $this->request('GET', $path, $options);
    }
}
