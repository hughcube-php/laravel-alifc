<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午.
 */

namespace HughCube\Laravel\AliFC\Actions;

use HughCube\Laravel\AliFC\Queue\Job;
use HughCube\Laravel\Knight\Routing\Controller;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class InvokeAction extends Controller
{
    /**
     * @return Response
     *
     * @throws BindingResolutionException
     */
    public function action(): Response
    {
        if (! $this->isAllow()) {
            throw new AccessDeniedHttpException();
        }

        $content = $this->getRequest()->getContent();

        $job = null;
        try {
            $job = $this->parseJob($content);
            $job->fire();

            return $this->asJson(['job' => $job->getJobId()]);
        } catch (Throwable $exception) {
            $this->getQueueFailer()->log('alifc', 'default', $content, $exception);
            app(ExceptionHandler::class)->report($exception);
            $jobId = is_object($job) && method_exists($job, 'getJobId') ? $job->getJobId() : null;

            return $this->asJson(['job' => $jobId, 'message' => $exception->getMessage()], 500);
        }
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

    /**
     * @param  mixed  $content
     * @return Job
     */
    protected function parseJob($content): Job
    {
        return new Job($this->getContainer(), $this->parseJobPayload($content));
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
            if (isset($json['payload']) && is_string($json['payload'])) {
                return $json['payload'];
            }
        } catch (\Throwable $exception) {
        }

        return $content;
    }
}
