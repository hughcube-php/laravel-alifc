<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/10/26
 * Time: 10:13
 */

namespace HughCube\Laravel\AliFC\Queue;

use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

trait ParseJob
{
    /**
     * @return FailedJobProviderInterface
     * @throws BindingResolutionException
     */
    protected function getQueueFailer(): FailedJobProviderInterface
    {
        return $this->getJobContainer()->make('queue.failer');
    }

    /**
     * @return IlluminateContainer
     */
    protected function getJobContainer(): IlluminateContainer
    {
        return IlluminateContainer::getInstance();
    }

    /**
     * @param $content
     * @return Job
     */
    protected function parseJob($content): Job
    {
        return new Job($this->getJobContainer(), $this->parseJobPayload($content));
    }

    /**
     * @param  string  $content
     * @return string
     */
    protected function parseJobPayload(string $content): string
    {
        /** 兼容来自触发器 */
        try {
            $json = json_decode($content, true);
            if (isset($json['triggerTime'], $json['triggerName'], $json['payload']) && is_string($json['payload'])) {
                return $json['payload'];
            }
        } catch (\Throwable $exception) {
        }

        return $content;
    }
}
