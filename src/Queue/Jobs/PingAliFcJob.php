<?php

namespace HughCube\Laravel\AliFC\Queue\Jobs;

use GuzzleHttp\RequestOptions;
use HughCube\Laravel\AliFC\AliFC;
use HughCube\Laravel\AliFC\Client;
use HughCube\PUrl\Url as PUrl;
use HughCube\StaticInstanceInterface;
use HughCube\StaticInstanceTrait;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LogLevel;

class PingAliFcJob implements StaticInstanceInterface, ShouldQueue
{
    use StaticInstanceTrait;

    /**
     * @var array|string|null
     */
    protected $logChannel = null;

    /**
     * @var string|int|null
     */
    protected $pid = null;

    /**
     * @var array
     */
    protected $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function handle(): Response
    {
        $start = Carbon::now();
        $response = $this->getClient()->request($this->getMethod(), $this->getUrl(), [
            RequestOptions::TIMEOUT => $this->getTimeout(),
            RequestOptions::ALLOW_REDIRECTS => $this->getAllowRedirects(),
        ]);

        /** wait */
        $response->getStatusCode();
        $end = Carbon::now();

        $this->log(LogLevel::INFO, sprintf(
            '%sms [%s] [%s] %s %s',
            round(($end->getPreciseTimestamp() - $start->getPreciseTimestamp()) / 1000, 2),
            $this->getRequestId($response),
            $response->getStatusCode(),
            $this->getMethod(),
            $this->getUrl()
        ));

        return $response;
    }

    protected function getRequestId($response): ?string
    {
        if (! $response instanceof Response) {
            return null;
        }

        foreach ($response->getHeaders() as $name => $header) {
            if (Str::endsWith(strtolower($name), 'request-id')) {
                return $response->getHeaderLine($name);
            }
        }

        return null;
    }

    protected function getClient(): Client
    {
        return AliFC::client($this->data['client'] ?? null);
    }

    protected function getUrl(): string
    {
        $url = $this->data['url'] ?? 'alifc_ping';
        if (is_string($url) && PUrl::isUrlString($url)) {
            return $url;
        }

        $appUrl = PUrl::parse(config('app.url'));
        $purl = PUrl::parse(Route::has($url) ? route($url) : URL::to($url));
        if ($appUrl instanceof PUrl && $purl instanceof PUrl) {
            $purl = $purl->withScheme($appUrl->getScheme());
        }

        return $purl instanceof PUrl ? $purl->toString() : $url;
    }

    protected function getMethod()
    {
        return $this->data['method'] ?? 'GET';
    }

    protected function getTimeout()
    {
        return $this->data['timeout'] ?? 30;
    }

    protected function getAllowRedirects()
    {
        if (0 >= ($redirects = $this->data['allow_redirects'] ?? 0)) {
            return false;
        }

        return [
            'max' => $redirects,
            'strict' => true,
            'referer' => true,
            'protocols' => ['https', 'http'],
        ];
    }

    /**
     * @param  string|null  $pid
     * @return $this
     */
    protected function setPid(string $pid = null): PingAliFcJob
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * @param  array|string|null  $channel
     * @return $this
     */
    public function setLogChannel($channel = null): PingAliFcJob
    {
        $this->logChannel = $channel;

        return $this;
    }

    /**
     * @return array|string|null
     */
    public function getLogChannel()
    {
        return $this->logChannel;
    }

    /**
     * @param  mixed  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function log($level, string $message, array $context = [])
    {
        $name = Str::afterLast(get_class($this), '\\');
        $this->pid = $this->pid ?: base_convert(abs(crc32(Str::random())), 10, 36);

        $message = sprintf('[%s-%s] %s', $name, $this->pid, $message);
        Log::channel($this->getLogChannel())->log($level, $message, $context);
    }
}
