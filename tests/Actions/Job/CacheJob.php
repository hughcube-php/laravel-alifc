<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/31
 * Time: 22:09
 */

namespace HughCube\Laravel\AliFC\Tests\Actions\Job;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class CacheJob
{
    protected $key = null;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function handle(): void
    {
        $ttl = Carbon::now()->addHours();
        $this->getCache()->set($this->key, $this->key, $ttl);
    }

    protected function getCache(): Repository
    {
        return Cache::store();
    }
}
