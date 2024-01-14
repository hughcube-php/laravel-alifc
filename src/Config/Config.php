<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2023/12/17
 * Time: 23:25.
 */

namespace HughCube\Laravel\AliFC\Config;

use Darabonba\OpenApi\Models\Config as FcConfig;
use Illuminate\Support\Arr;

class Config
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get(string $key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }

    public function getAccessKeyId(): ?string
    {
        return $this->get('AccessKeyID');
    }

    public function getType(): ?string
    {
        return $this->get('Type');
    }

    public function getAccessKeySecret(): ?string
    {
        return $this->get('AccessKeySecret');
    }

    public function getSecurityToken(): ?string
    {
        return $this->get('SecurityToken');
    }

    public function getRegionId(): ?string
    {
        return $this->get('RegionId');
    }

    public function getAccountId(): ?string
    {
        return $this->get('AccountId');
    }

    public function isInternal(): bool
    {
        return true == $this->get('Internal');
    }

    public function getScheme(): string
    {
        return $this->get('Scheme') ?? 'https';
    }

    public function getEndpoint(): string
    {
        $endpoint = $this->isInternal() ? '%s.%s-internal.fc.aliyuncs.com' : '%s.%s.fc.aliyuncs.com';

        return sprintf($endpoint, $this->getAccountId(), $this->getRegionId());
    }

    public function getHost(): ?string
    {
        return $this->get('Host') ?: null;
    }

    public function getFcBaseUri(): string
    {
        return sprintf('%s://%s', $this->getScheme(), $this->getEndpoint());
    }

    public function with(array $config): Config
    {
        /** @phpstan-ignore-next-line */
        return new static(array_merge($this->config, $config));
    }

    public function withRegionId(string $regionId): Config
    {
        return $this->with(['RegionId' => $regionId]);
    }
}
