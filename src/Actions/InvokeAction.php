<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午.
 */

namespace HughCube\Laravel\AliFC\Actions;

use HughCube\Laravel\AliFC\Queue\Job;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Events\Dispatcher as EventsDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class InvokeAction extends Action
{
    protected static $hasListenEvents;

    /**
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function action(): Response
    {
        if (! $this->isAllow()) {
            throw new AccessDeniedHttpException();
        }

        $job = $this->parseJob($this->getPayload());
        if (! $job instanceof Job) {
            throw new BadRequestHttpException('Unexpected payload.');
        }

        $this->listenForEvents();
        $this->getQueueWorker()->process('alifc', $job, new WorkerOptions());

        return new JsonResponse([
            'code' => 200,
            'message' => 'ok',
            'data' => [
                'job' => $job->getJobId(),
            ],
        ]);
    }

    /**
     * @throws BindingResolutionException
     */
    protected function isAllow(): bool
    {
        /** allow if set */
        if (true === filter_var(getenv('HUGHCUBE_ALIFC_ALLOW_FIRE_JOB'), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        $paths = Collection::wrap($this->getRequest()->header('x-fc-control-path'));

        return $paths->isNotEmpty() && !$paths->containsStrict('/http-invoke');
    }

    /**
     * @param  string|null  $payload
     * @return Job
     */
    protected function parseJob(?string $payload): ?Job
    {
        if (empty($payload)) {
            return null;
        }

        return new Job($this->getContainer(), $payload, 'alifc', 'default');
    }

    /**
     * @throws BindingResolutionException
     */
    protected function getPayload(): ?string
    {
        $payload = $this->getRequest()->json('payload');

        return $payload ?: $this->getRequest()->getContent() ?: null;
    }

    /**
     * @throws BindingResolutionException
     */
    protected function listenForEvents()
    {
        if (! static::$hasListenEvents) {
            return;
        }

        static::$hasListenEvents = true;
        $this->getEvents()->listen(JobFailed::class, function (JobFailed $event) {
            $this->logFailedJob($event);
        });
    }

    /**
     * @throws BindingResolutionException
     */
    protected function logFailedJob(JobFailed $event)
    {
        $this->getQueueFailer()->log(
            $event->connectionName,
            $event->job->getQueue(),
            $event->job->getRawBody(),
            $event->exception
        );
    }

    /**
     * @throws BindingResolutionException
     */
    protected function getRequest(): Request
    {
        return $this->getContainer()->make('request');
    }

    /**
     * @throws BindingResolutionException
     */
    protected function getQueueWorker(): Worker
    {
        return $this->getContainer()->make('queue.worker');
    }

    /**
     * @throws BindingResolutionException
     */
    protected function getQueueFailer(): FailedJobProviderInterface
    {
        return $this->getContainer()->make('queue.failer');
    }

    /**
     * @throws BindingResolutionException
     */
    protected function getEvents(): EventsDispatcher
    {
        return $this->getContainer()->make('events');
    }
}
