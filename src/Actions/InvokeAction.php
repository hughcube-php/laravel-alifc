<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午
 */

namespace HughCube\Laravel\AliFC\Actions;

use HughCube\Laravel\AliFC\Queue\ParseJob;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class InvokeAction extends Action
{
    use ParseJob;

    /**
     * @return Response
     * @throws BindingResolutionException
     */
    public function action(): Response
    {
        if (!$this->isAllow()) {
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
            return $this->asJson(['job' => $jobId], 500, $exception->getMessage());
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
}
