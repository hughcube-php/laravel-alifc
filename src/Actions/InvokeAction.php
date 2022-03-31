<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午.
 */

namespace HughCube\Laravel\AliFC\Actions;

use HughCube\Laravel\AliFC\Queue\Job;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class InvokeAction
{
    /**
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function action(): JsonResponse
    {
        if (! $this->isAllow()) {
            throw new AccessDeniedHttpException();
        }

        try {
            $job = $this->parseJob($this->getPayload());
            $job->fire();
        } catch (Throwable $exception) {
            $this->getQueueFailer()->log(
                $this->getConnection(),
                $this->getQueue(),
                $this->getRequest()->getContent(),
                $exception
            );
            throw $exception;
        }

        return new JsonResponse(['code' => 200, 'message' => 'ok', 'data' => ['job' => $job->getJobId()]]);
    }

    protected function isAllow(): bool
    {
        /** Default allow if not set */
        if (false === ($value = getenv('HUGHCUBE_ALIFC_ALLOW_FIRE_JOB'))) {
            return true;
        }

        return true === filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return FailedJobProviderInterface
     *
     * @throws BindingResolutionException
     */
    protected function getQueueFailer(): FailedJobProviderInterface
    {
        return $this->getContainer()->make('queue.failer');
    }

    protected function getConnection(): ?string
    {
        return null;
    }

    protected function getQueue(): ?string
    {
        return null;
    }

    /**
     * @param  string|null  $payload
     * @return Job
     */
    protected function parseJob(?string $payload): Job
    {
        return new Job($this->getContainer(), $payload);
    }

    /**
     * @return string
     */
    protected function getPayload(): string
    {
        $payload = $this->getRequest()->json('payload');

        return $payload ?: $this->getRequest()->getContent();
    }

    /**
     * @return Request
     * @phpstan-ignore-next-line
     *
     * @throws
     */
    protected function getRequest(): Request
    {
        return $this->getContainer()->make('request');
    }

    /**
     * @return IlluminateContainer
     */
    protected function getContainer(): IlluminateContainer
    {
        return IlluminateContainer::getInstance();
    }

    /**
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function __invoke(): JsonResponse
    {
        return $this->action();
    }
}
