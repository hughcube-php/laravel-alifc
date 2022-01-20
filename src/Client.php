<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/23
 * Time: 11:20.
 */

namespace HughCube\Laravel\AliFC;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use HughCube\Laravel\AliFC\Fc\Auth;
use HughCube\PUrl\Url as PUrl;
use Illuminate\Support\Arr;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class Client extends Auth
{
    /**
     * @throws GuzzleException
     */
    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        return $this->getHttpClient()->request(strtoupper($method), $uri, $options);
    }

    /**
     * @param  string  $method
     * @param  string  $path
     * @param  array  $options
     * @return array
     * @throws GuzzleException
     * @throws JsonException
     */
    public function jsonRequest(string $method, string $path, array $options = []): array
    {
        $response = $this->request(strtoupper($method), $path, $options);
        $contents = $response->getBody()->getContents();
        $results = json_decode($contents, true);

        if (JSON_ERROR_NONE != ($code = json_last_error())) {
            throw new JsonException(json_last_error_msg(), $code);
        }

        return $results;
    }

    /**
     * @param  string  $service
     * @param  string  $function
     * @param  string|null  $qualifier
     * @param  string|null  $payload
     * @param  array  $options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function invoke(
        string $service,
        string $function,
        ?string $qualifier = null,
        ?string $payload = null,
        array $options = []
    ): ResponseInterface {

        $service = empty($qualifier) ? $service : "$service.$qualifier";
        $path = sprintf('/%s/services/%s/functions/%s/invocations', $this->getApiVersion(), $service, $function);

        return $this->request('POST', $path, [
            RequestOptions::HEADERS => array_filter([
                'X-Fc-Invocation-Type' => Arr::get($options, 'type', 'Sync'),
                'X-Fc-Log-Type' => Arr::get($options, 'log', 'None'),
                'X-Fc-Stateful-Async-Invocation-Id' => Arr::get($options, 'id')
            ]),
            RequestOptions::BODY => $payload,
        ]);
    }

    /**
     * @param  string  $name
     * @param  string  $description
     * @param  array  $options
     * @return array
     * @throws GuzzleException
     * @throws JsonException
     *
     * @see https://help.aliyun.com/document_detail/175256.html
     */
    public function createService(string $name, string $description = "", array $options = []): array
    {
        $path = sprintf('/%s/services', $this->getApiVersion());
        return $this->jsonRequest('POST', $path, [
            RequestOptions::JSON => array_merge($options, [
                'serviceName' => $name,
                'description' => $description,
            ]),
        ]);
    }

    /**
     * @param  string  $name
     * @param  string|null  $qualifier
     * @return array
     * @throws Throwable
     *
     * @see https://help.aliyun.com/document_detail/189225.html
     */
    public function getService(string $name, ?string $qualifier = null): array
    {
        $name = empty($qualifier) ? $name : "$name.$qualifier";
        $path = sprintf('/%s/services/%s', $this->getApiVersion(), $name);

        return $this->jsonRequest('GET', $path);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function updateCustomDomain(
        string $domain,
        ?array $cert = null,
        ?array $route = null,
        string $protocol = null
    ): ?array {
        $path = sprintf('/%s/custom-domains/%s', $this->getApiVersion(), $domain);

        return $this->jsonRequest('PUT', $path, [
            RequestOptions::JSON => array_filter([
                'domainName' => $domain,
                'certConfig' => $cert,
                'protocol' => $protocol,
                'routeConfig' => $route,
            ]),
        ]);
    }
}
