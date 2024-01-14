<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2024/1/14
 * Time: 12:15
 */

namespace HughCube\Laravel\AliFC\Util;

use GuzzleHttp\RequestOptions;
use HughCube\Laravel\AliFC\Client;
use HughCube\PUrl\HUrl;
use Illuminate\Support\Collection;
use Psr\Http\Message\RequestInterface;

class OpenApiUtil
{
    public static function getCanonicalQueryString($query)
    {
        ksort($query);

        $params = [];
        foreach ($query as $k => $v) {
            if (null === $v) {
                continue;
            }
            $str = rawurlencode($k);
            if ('' !== $v && null !== $v) {
                $str .= '='.rawurlencode($v);
            } else {
                $str .= '=';
            }
            $params[] = $str;
        }

        return implode('&', $params);
    }

    public static function hash($string, $signatureAlgorithm)
    {
        return bin2hex(hash('sha256', $string, true));
    }

    public static function signature($secret, $string, $signatureAlgorithm)
    {
        return bin2hex(hash_hmac('sha256', $string, $secret, true));
    }

    public static function completeRequestMiddleware(Client $client, callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($client, $handler) {
            if (!$request->hasHeader('Host') && !empty($host = $client->getConfig()->getHost())) {
                $request = $request->withHeader('Host', $host);
            }

            if (!$request->hasHeader('Host')) {
                $request = $request->withHeader('Host', $request->getUri()->getHost());
            }

            if (!$request->hasHeader('Date')) {
                $request = $request->withHeader('Date', gmdate('D, d M Y H:i:s T'));
            }

            if (!$request->hasHeader('Content-Type')) {
                $request = $request->withHeader('Content-Type', 'application/octet-stream');
            }

            /** When you forcibly change the host using HTTPS, HTTPS authentication must be disabled. */
            if ('https' === $request->getUri()->getScheme()
                && $request->getUri()->getHost() !== $request->getHeaderLine('Host')
            ) {
                $options[RequestOptions::VERIFY] = false;
            }

            return $handler($request, $options);
        };
    }

    public static function fcApiSignatureRequestMiddleware(Client $client, callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($client, $handler) {
            if (empty($options['fcApi'])) {
                return $handler($request, $options);
            }

            /** 签名相关信息方式 */
            $apiVersion = $client->getConfig()->getVersion();
            $accessKeyId = $client->getConfig()->getAccessKeyId();
            $accessKeySecret = $client->getConfig()->getAccessKeySecret();
            $signatureAlgorithm = $client->getConfig()->getSignatureAlgorithm();

            /** 默认Header */
            $request = $request
                ->withHeader('X-Acs-Version', $apiVersion)
                ->withHeader('Host', $client->getConfig()->getEndpoint())
                ->withHeader('X-Acs-Date', gmdate('Y-m-d\\TH:i:s\\Z'))
                ->withHeader('X-Acs-Signature-Nonce', md5(uniqid().uniqid(md5(microtime(true)), true)))
                ->withoutHeader('Authorization');

            /** body因子 */
            $request->getBody()->rewind();
            $request = $request->withHeader(
                'x-acs-content-sha256',
                OpenApiUtil::hash($request->getBody()->getContents(), $signatureAlgorithm)
            );

            /** 参与签名的header */
            $signHeaders = Collection::empty();
            foreach ($request->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    $signHeaders = $signHeaders->put(strtolower($name), $value);
                    break;
                }
            }
            $signHeaders = $signHeaders->sortKeys();

            /** url query因子 */
            $canonicalQueryString = OpenApiUtil::getCanonicalQueryString(
                HUrl::instance($request->getUri())->getQueryArray()
            );

            /** header 因子 */
            $canonicalHeaderString = $signHeaders
                ->map(function ($v, $k) {
                    $value = trim(str_replace(["\t", "\n", "\r", "\f"], '', $v));
                    return sprintf("%s:%s\n", strtolower($k), $value);
                })
                ->join("");
            $canonicalHeaderString = $canonicalHeaderString ?: "\n";


            /** 组装所有的签名因子 */
            $canonicalRequest = strtoupper($request->getMethod())."\n"
                .($request->getUri()->getPath() ?: '/')."\n"
                .$canonicalQueryString."\n"
                .$canonicalHeaderString."\n"
                .$signHeaders->keys()->join(';')."\n"
                .$request->getHeaderLine('x-acs-content-sha256');

            /** 签名 */
            $signature = OpenApiUtil::signature(
                $accessKeySecret,
                $signatureAlgorithm."\n".OpenApiUtil::hash($canonicalRequest, $signatureAlgorithm),
                $signatureAlgorithm
            );

            /** 设置最终签名 */
            $request = $request
                ->withHeader('Authorization', sprintf(
                    '%s Credential=%s,SignedHeaders=%s,Signature=%s',
                    $signatureAlgorithm,
                    $accessKeyId,
                    $signHeaders->keys()->join(';'),
                    $signature
                ));

            return $handler($request, $options);
        };
    }
}
