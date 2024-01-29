<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2024/1/14
 * Time: 12:15.
 */

namespace HughCube\Laravel\AliFC\Util;

use Closure;
use HughCube\Laravel\AliFC\Client;
use HughCube\PUrl\HUrl;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;

class OpenApiUtil
{
    public static function hash($string, $signatureAlgorithm): string
    {
        return bin2hex(hash('sha256', $string, true));
    }

    public static function signature($secret, $string, $signatureAlgorithm): string
    {
        return bin2hex(hash_hmac('sha256', $string, $secret, true));
    }

    public static function completeRequestMiddleware(Client $client, callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($client, $handler) {
            if (! $request->hasHeader('Host') && ! empty($host = $client->getConfig()->getHost())) {
                $request = $request->withHeader('Host', $host);
            }

            if (! $request->hasHeader('Host')) {
                $request = $request->withHeader('Host', $request->getUri()->getHost());
            }

            if (! $request->hasHeader('Date')) {
                $request = $request->withHeader('Date', gmdate('D, d M Y H:i:s T'));
            }

            if (! $request->hasHeader('Content-Type')) {
                $request = $request->withHeader('Content-Type', 'application/octet-stream');
            }

            if (Str::startsWith($request->getHeaderLine('User-Agent'), 'GuzzleHttp')) {
                $request = $request->withoutHeader('User-Agent');
            }

            return $handler($request, $options);
        };
    }

    public static function fcApiSignatureRequestMiddleware(Client $client, callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($client, $handler) {
            if (empty($options['extra']['is_alifc_api'])) {
                return $handler($request, $options);
            }

            /** 签名相关信息方式 */
            $apiVersion = $client->getConfig()->getVersion();
            $accessKeyId = $client->getConfig()->getAccessKeyId();
            $accessKeySecret = $client->getConfig()->getAccessKeySecret();
            $signatureAlgorithm = $client->getConfig()->getSignatureAlgorithm();

            /** 如果Header是多个, 修改为line模式 */
            foreach (array_keys($request->getHeaders()) as $name) {
                $request = $request->withHeader($name, $request->getHeaderLine($name));
            }

            /** 默认Header */
            $request = $request
                ->withHeader('X-Acs-Version', $apiVersion)
                ->withHeader('Host', $client->getConfig()->getEndpoint())
                ->withHeader('X-Acs-Date', gmdate('Y-m-d\\TH:i:s\\Z', strtotime($request->getHeaderLine('Date'))))
                ->withoutHeader('Authorization');

            /** Body Hash Header */
            $request->getBody()->rewind();
            $request = $request->withHeader(
                'X-Acs-Content-Sha256',
                OpenApiUtil::hash($request->getBody()->getContents(), $signatureAlgorithm)
            );

            /** Nonce Header */
            $nonce = sprintf('%s-%s-%s', microtime(), $request->getHeaderLine('X-Acs-Content-Sha256'), Str::random(32));
            $request = $request->withHeader('X-Acs-Signature-Nonce', sprintf('%s%s', md5($nonce), abs(crc32($nonce))));

            /** 参与签名的header */
            $signHeaders = Collection::make($request->getHeaders())->mapWithKeys(function ($value, $key) {
                return [strtolower($key) => $value[0]];
            })->sortKeys();

            /** header 因子 */
            $canonicalHeaderString = $signHeaders->map(function ($v, $k) {
                $value = trim(str_replace(["\t", "\n", "\r", "\f"], '', $v));

                return sprintf("%s:%s\n", strtolower($k), $value);
            })->join('');
            $canonicalHeaderString = $canonicalHeaderString ?: "\n";

            /** url query因子 */
            $canonicalQueryString = Collection::make(HUrl::instance($request->getUri())->getQueryArray())
                ->sortKeys()->map(function ($v, $k) {
                    return sprintf('%s=%s', rawurlencode($k), rawurlencode($v));
                })->join('&');

            /** 拼接所有的签名因子 */
            $canonicalRequest = strtoupper($request->getMethod())."\n"
                .($request->getUri()->getPath() ?: '/')."\n"
                .$canonicalQueryString."\n"
                .$canonicalHeaderString."\n"
                .$signHeaders->keys()->join(';')."\n"
                .$request->getHeaderLine('X-Acs-Content-Sha256');

            /** 签名 */
            $signature = OpenApiUtil::signature(
                $accessKeySecret,
                $signatureAlgorithm."\n".OpenApiUtil::hash($canonicalRequest, $signatureAlgorithm),
                $signatureAlgorithm
            );

            /** 设置最终签名 */
            $request = $request->withHeader('Authorization', sprintf(
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
